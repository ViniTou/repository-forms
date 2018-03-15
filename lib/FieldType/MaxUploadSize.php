<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RepositoryForms\FieldType;

trait MaxUploadSize
{
    public function getMaxUploadSize()
    {
        static $value = null;
        if ($value === null) {
            $value = $this->str2bytes(ini_get('upload_max_filesize'));
        }

        return $value;
    }

    private function str2bytes($str)
    {
        $str = strtoupper(trim($str));

        $value = substr($str, 0, -1);
        $unit = substr($str, -1);
        switch ($unit) {
            case 'G':
                $value *= 1024;
            case 'M':
                $value *= 1024;
            case 'K':
                $value *= 1024;
        }

        return (int) $value;
    }
}
