<?php

namespace App\Helpers;

class QCHelper
{
    public static function setData($data)
    {
        $qcArr = [];
        if (count($data) > 0) {
            foreach ($data as $d) {
                array_push($qcArr, [
                    "qc_id" => $d->qc_id,
                    "date" => $d->created_at,
                    "qc_status" => $d->qc_status,
                    "object_id" => $d->object_id,
                    "object_type" => $d->object_type,
                    "ncr_failed_id" => $d->ncr_failed_id,
                    "photo_url" => $d->photo,
                    "signature" => $d->signature
                ]);
            }
        }

        return $qcArr;
    }
}
