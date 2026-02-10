<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'contact_no',
        'apk_file_path',
        'apk_version',
        'apk_file_size',
        'apk_release_notes',
        'tablet_apk_file_path',
        'tablet_apk_version',
        'tablet_apk_file_size',
        'tablet_apk_release_notes',
        'terms_and_conditions_content',
        'terms_and_conditions_html',
        'privacy_policy_content',
        'privacy_policy_html',
        'machine_serial_format',
        'machine_serial_prefix',
        'machine_serial_length',
    ];
}
