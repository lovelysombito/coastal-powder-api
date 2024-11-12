<?php

namespace App\Jobs\HubSpot;

use App\Models\Deal;
use App\Models\LineItems;
use App\Models\Job;
use App\Models\Integration\HubSpot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncDealLineItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $dealId;
    public $tries = 0;
    public $maxExceptions = 3;

    public function __construct($dealId)
    {
        $this->dealId = $dealId;
    }

    public function retryUntil() {
        return now()->addHours(18);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("SyncDealLineItems@handle - {$this->dealId}", ["dealId" => $this->dealId]);

        $integration = HubSpot::where(['platform' => 'HUBSPOT', 'integration_status'=>'Connected'])->first();
        if (!$integration) {
            Log::warning("SyncDealLineItems - HubSpot integration is not connected for ".$this->dealid, ["dealId"=> $this->dealId, "integration"=>$integration]);
            return;
        }

        if ($hubspotRetryTimestamp = Cache::get('hubspot-api-retry-timeout', null)) {
            Log::notice("SyncDealLineItems - HubSpot API rate limit activated, retrying in ".$hubspotRetryTimestamp - time()." seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "integration"=>$integration, "dealId"=>$this->dealId]);
            $this->release($hubspotRetryTimestamp - time());
            return;
        }

        try {

            $hubspotDeal = $integration->getDeal($this->dealId);
            if (!$hubspotDeal) {
                Log::error("SyncDealLineItems@handle - HubSpot deal not found for id ".$this->dealId, ["dealId"=>$this->dealId, "integration"=>$integration]);
                return;
            }

            $companies = $integration->getDealCompanyAssociations($this->dealId);
            $hubspotCompanyName = "";
            if ($companies->getResults()) {
                $companyId = $companies->getResults()[0]->getId();
                $hubspotCompany = $integration->getCompany($companyId);
                $hubspotCompanyName = $hubspotCompany->getProperties()['name'];
            }

            $contacts = $integration->getDealContactAssociations($this->dealId);
            $contactRecords = [];

            if ($contacts->getResults()) {
                $contactIds = array_map(function($contact) {
                    return $contact->getId();
                }, $contacts->getResults());

                $contactRecords = $integration->getBatchContacts($contactIds);
            }
            
            $contactNames = [];
            if ($contactRecords && $contactRecords->getResults()) {
                foreach($contactRecords->getResults() as $key => $contact) {
                    $name = "";
                    if (isset($contact->getProperties()['firstname'])) {
                        $name = $contact->getProperties()['firstname'];
                    }
                    if (isset($contact->getProperties()['lastname'])) {
                        $name = $name . " " .$contact->getProperties()['lastname'];
                    }
                    if (!$name) {
                        $name = $contact->getProperties()['email'];
                    }
                    array_push($contactNames, $name);
                }
            }

            $names = implode('|', $contactNames);
            if ($names && $hubspotCompanyName) {
                $names = " | " . $names;
            }

            $hubspotCompanyName = $hubspotCompanyName . $names;

            $deal = null;
            //Has the deal been synced before?
            if ($deal = Deal::where('hs_deal_id', $this->dealId)->first()) {
                $deal->update(HubSpot::castDealToArrayProperties($hubspotDeal, $hubspotCompanyName));
            } else {
                Log::error("SyncDealLineItems@handle - Deal ".$this->dealId . " has not been synced yet", ["dealId"=>$this->dealId, "integration"=>$integration]);
                return;
            }

            $items = $integration->getDealLineItemAssociations($this->dealId);
            $items = array_map(function($item) {
                return $item->getId();
            }, $items->getResults());


            $lineItems = HubSpot::mapLineItemsToArrayProperties($integration->getBatchLineItems($items));
            if (!$lineItems) {
                Log::error("SyncDealLineItems@handle - No line items found for deal ".$this->dealId, ["dealId"=>$this->dealId, "integration"=>$integration]);

                if ($deal->lineitems()) {
                    Log::info("SyncDealLineItems@handle - Remove all related lineitems for ".$deal->deal_id, ["dealId"=>$this->dealId, "integration"=>$integration]);
                    $deal->lineitems()->delete();
                }

                if ($deal->jobs()) {
                    Log::info("SyncDealLineItems@handle - Remove all related jobs for ".$deal->deal_id, ["dealId"=>$this->dealId, "integration"=>$integration]);
                    $deal->jobs()->delete();
                }
                return;
            }

            // https://github.com/jacktaylorgroup/coastal-powder-coating-api/issues/144 Check to see if we need to remove LineItems that may be stored in the DB but no longer required
        
            $colourMaterialCombinations = [];
            $uniqueColourMaterialCombinations = [];
            foreach($lineItems as $item) {
                array_push($uniqueColourMaterialCombinations, [
                    "colour" => $item['colour'],
                    'material' => $item['material'],
                    'bay' => $item['coating_line'],
                    'treatment' => $item['treatment'],
                ]);
                
                $colourMaterialCombinations[$item['colour']][$item['material']][$item['coating_line']][$item['treatment']][] = $item;

            }
            $uniqueColourMaterialCombinations = array_map("unserialize", array_unique(array_map("serialize", $uniqueColourMaterialCombinations)));
            Log::info("SyncDealLineItems@handle - Query unique colour combinations", ["dealId"=>$this->dealId, "integration"=>$integration, "job_combinations" => $uniqueColourMaterialCombinations]);

            $jobPrefix = $deal->deal_name ?? $deal->hs_deal_id;

            // The index to being numbering jobs from
            foreach($colourMaterialCombinations as $colour => $colourCombination) {
                foreach($colourCombination as $material => $materialCombination) {
                    foreach($materialCombination as $bay => $bayCombination) {
                        foreach ($bayCombination as $treatment => $uniqueJob) {

                            $treatmentBays = str_split($treatment);
                            $bayValues = [];
                            $lineitemBayValues = [];
                            $bayValues['chem_bay_required'] = 'no';
                            $bayValues['treatment_bay_required'] = 'no';
                            $bayValues['burn_bay_required'] = 'no';
                            $bayValues['blast_bay_required'] = 'no';
                            $bayValues['powder_bay_required'] = 'no';
                            $lineitemBayValues['chem_status'] = 'na';
                            $lineitemBayValues['treatment_status'] = 'na';
                            $lineitemBayValues['burn_status'] = 'na';
                            $lineitemBayValues['blast_status'] = 'na';
                            $lineitemBayValues['powder_status'] = 'na';


                            foreach($treatmentBays as $i => $bayCode) {
                                $stageStatus = 'waiting';
                                if ($i == 0) {
                                    $stageStatus = 'ready';
                                }
                                switch($bayCode) {
                                    case 'S':
                                        $bayValues['chem_bay_required'] = 'yes';
                                        $bayValues['chem_status'] = $stageStatus;
                                        $lineitemBayValues['chem_status'] = $stageStatus;
                                        break;
                                    case 'T':
                                        $bayValues['treatment_bay_required'] = 'yes';
                                        $bayValues['treatment_status'] = $stageStatus;
                                        $lineitemBayValues['treatment_status'] = $stageStatus;

                                        break;
                                    case 'F':
                                        $bayValues['burn_bay_required'] = 'yes';
                                        $bayValues['burn_status'] = $stageStatus;
                                        $lineitemBayValues['burn_status'] = $stageStatus;
                                        break;
                                    case 'B':
                                        $bayValues['blast_bay_required'] = 'yes';
                                        $bayValues['blast_status'] = $stageStatus;
                                        $lineitemBayValues['blast_status'] = $stageStatus;
                                        break;
                                    case 'C':
                                    case 'P':
                                        $bayValues['powder_bay_required'] = 'yes';
                                        $bayValues['powder_status'] = $stageStatus;
                                        $lineitemBayValues['powder_status'] = $stageStatus;
                                        break;
                                }
                            }

                            if (!$job = Job::where(['colour' => $colour, 'material' => $material, 'powder_bay' => $bay, 'treatment' => $treatment, 'deal_id' => $deal->deal_id, 'job_status' => 'Ready'])->first()) {
                                // Does a job already exist for this combination? If so we may be able to add to this job depending on its status, otherwise we need to create a new job
                                
                                $job = Job::create(array_merge([
                                    'colour' => $colour,
                                    'material' => $material,
                                    'powder_bay' => $bay,
                                    'treatment' => $treatment,
                                    'deal_id' => $deal->deal_id,
                                    'job_prefix' => $jobPrefix,
                                ], $bayValues));
                                Log::info("SyncDealLineItems@handle - Created job ".$job->job_id, ["dealId"=>$this->dealId, "integration"=>$integration]);
                            } else {

                                if ($job->chem_status) {
                                    unset($bayValues['chem_status']);
                                }
                                if ($job->treatment_status) {
                                    unset($bayValues['treatment_status']);
                                }
                                if ($job->burn_status) {
                                    unset($bayValues['burn_status']);
                                }
                                if ($job->blast_status) {
                                    unset($bayValues['blast_status']);
                                }
                                if ($job->powder_status) {
                                    unset($bayValues['powder_status']);
                                }

                                $job->update(array_merge([
                                    'job_prefix' => $jobPrefix,
                                ], $bayValues));
                                Log::info("SyncDealLineItems@handle - Updated job ".$job->job_id, ["dealId"=>$this->dealId, "integration"=>$integration]);
                            }

                            foreach($uniqueJob as $lineitem) {
                                if ($existingLineItem = LineItems::where('hs_deal_lineitem_id', $lineitem['line_item_id'])->first()) {
                                    // We need to check if just updating the line item, or if we need to move this line item to a different job is required
                                    
                                    $currentJob = $existingLineItem->job;
                                    if ($currentJob->job_id === $job->job_id || ($currentJob->material === $material && $currentJob->colour === $colour && $currentJob->powder_bay === $bay && $currentJob->treatment === $treatment)) {
                                        // The line item matches the treatments, bays, etc.. so we don't need to change the linked job
                                        $existingLineItem->update(array_merge(HubSpot::castLineItemToJobProperties($lineitem), $lineitemBayValues));
                                        Log::info("SyncDealLineItems@handle - Updated line item ".$existingLineItem->line_item_id, ["dealId"=>$this->dealId, "integration"=>$integration]);
                                    } else {
                                        
                                        // The line item material, colours, etc.. don't match the current job as they have been updated so we need to move the line item to a new job
                                        $lineitemProperties = HubSpot::castLineItemToJobProperties($lineitem);
                                        $lineitemProperties['job_id'] = $job->job_id;
                                        $existingLineItem->update(array_merge($lineitemProperties, $lineitemBayValues));
                                        Log::info("SyncDealLineItemshandle - Updated line item ".$existingLineItem->line_item_id, ["dealId"=>$this->dealId, "integration"=>$integration]);
                                    }
                                } else {
                                    $lineitemProperties = HubSpot::castLineItemToJobProperties($lineitem);
                                    $lineitemProperties['job_id'] = $job->job_id;
                                    $lineitemProperties['deal_id'] = $deal->deal_id;

                                    $newLineItem = LineItems::create(array_merge($lineitemProperties, $lineitemBayValues));
                                    Log::info("SyncDealLineItems@handle - Created line item ".$newLineItem->line_item_id, ["dealId"=>$this->dealId, "integration"=>$integration]);
                                }
                            }
                        }
                    }
                } 
            }

            $jobs = $deal->jobs;
            
            foreach($jobs as $job) {
                if (count($job->lines) === 0) {
                    $job->delete();
                }
            }

            $deal->load('jobs');

            $jobs = $deal->jobs->sortBy('created_at'); // Refresh jobs
            $total = count($jobs);
            $count = 1;
            foreach($jobs as $job) {
                $job->update(['job_number' => $count . '/' . $total]);
                $count++;
            }
        } catch (\HubSpot\Client\Crm\Companies\ApiException | \HubSpot\Client\Crm\Contacts\ApiException | \HubSpot\Client\Crm\Deals\ApiException | \HubSpot\Client\Crm\LineItems\ApiException $e) {
            if ($e->getCode() === 429) {
                $retryAfter = 10;
                Log::notice("SyncDealLineItems - HubSpot API rate limit exceeded, retrying in {$retryAfter} seconds", ["jobId" => $this->uuid ?? 'job_id_doesnt_exist', "integration"=>$integration, "dealId"=>$this->dealId]);
                Cache::put('hubspot-api-retry-timeout', now()->addSeconds($retryAfter)->timestamp, $retryAfter);

                $this->release(($retryAfter));
                return;
            }

            \Sentry\captureException($e);
            Log::error("SyncDealLineItems - HubSpot API exception - ".$e->getMessage(), ["integration"=>$integration, "dealId"=>$this->dealId, "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],]);
            return;
        } catch (\Exception $e) {
            Log::error("SyncDealLineItems - Something has gone wrong: ".$e->getMessage(), [
                "integration"=>$integration, "dealId"=>$this->dealId,
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                'user'=>'hubspot_webhooks'
            ]);
            \Sentry\captureException($e);
            return;
        }
    }
}
