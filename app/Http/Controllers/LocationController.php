<?php

namespace App\Http\Controllers;

use App\Events\JobEvent;
use App\Helpers\ResponseHelper;
use App\Models\Location;
use Exception;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function getAllLocations(Request $request) {
        try {
            $locations = Location::all();
            if (count($locations) == 0) {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), $locations, 'Locations List');
            }

            return ResponseHelper::responseMessage(config('constant.status_code.success'), $locations, 'Locations List');
        } catch (Exception $e) {
            return ResponseHelper::errorResponse($e->getMessage(), config('constant.status_code.bad_request'));
        }
    }

    public function addLocation(Request $request) {
        try {

            $request->validate([
                'location' => 'string|required',
            ]);

            $location = $request->location;

            $data = Location::where('location', $location)->first();
            if ($data) {
                return ResponseHelper::errorResponse('Locations already exist.', config('constant.status_code.not_found'));
            }
            
            $newLocation = new Location([
                'location' => $location,
            ]);

            $newLocation->save();
            event(new JobEvent('location'));
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $newLocation, 'Location has been successfully added');
        } catch (Exception $e) {
            return ResponseHelper::errorResponse($e->getMessage(), config('constant.status_code.bad_request'));
        }
    }

    public function editLocation(Request $request, $locationId) {
        try {
            if (!isset($request->location))
                return ResponseHelper::errorResponse('Location are missing', config('constant.status_code.not_found'));

            $location = $request->location;

            $data = Location::find($locationId);
            if (!$data) 
                return ResponseHelper::errorResponse('Location update invalid', config('constant.status_code.not_found'));

            $data->location = $location;
            $data->save();

            event(new JobEvent('location'));
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $data, 'Location has been successfully updated');
        } catch (Exception $e) {
            return ResponseHelper::errorResponse($e->getMessage(), config('constant.status_code.bad_request'));
        }
    }

    public function deleteLocation(Request $request, $locationId) {
        try {
            $data = Location::find($locationId);
            if (!$data) 
                return ResponseHelper::errorResponse('Location delete invalid', config('constant.status_code.not_found'));

            $data->delete();
            event(new JobEvent('location'));
            return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'Location has been successfully deleted');
        } catch (Exception $e) {
            return ResponseHelper::errorResponse($e->getMessage(), config('constant.status_code.bad_request'));
        }
    }
}
