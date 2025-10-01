<?php

declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RequestLog
 *
 * Represents an audit trail of API requests for monitoring, debugging,
 * and quota management purposes.
 *
 * @package App\Models
 *
 * @property int            $request_id    Primary key of the request log entry
 * @property int|null       $user_id       ID of the user associated with the request (nullable for guest/invalid)
 * @property string|null    $api_key       API key used in the request (nullable if not provided)
 * @property string         $method        HTTP method (GET, POST, PUT, DELETE, etc.)
 * @property string         $endpoint      The API endpoint path that was accessed
 * @property int            $status_code   HTTP response status code returned
 * @property string         $ip_address    Originating IP address of the request
 * @property string|null    $user_agent    User-Agent header for identifying client application
 * @property \Carbon\Carbon $requested_at  Exact timestamp of the request
 * @property string         $req_date      Date of the request (used for daily quota and reporting)
 */
class RequestLog extends Model
{

    use HasFactory;

    /**
     * Table associated with the model.
     *
     * @var string
     */
    protected $table = 'requests';

    /**
     * Primary key column for the model.
     *
     * @var string
     */
    protected $primaryKey = 'request_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * This table uses a custom timestamp (`requested_at`) instead of
     * Laravel's default `created_at`/`updated_at`.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable
        = [
            'user_id',
            'api_key',
            'method',
            'endpoint',
            'status_code',
            'ip_address',
            'user_agent',
            'requested_at',
            'req_date',
        ];

}
