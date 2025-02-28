<?php

namespace App\Models;

use App\Exceptions\Address\UnrecognizedCountryException;
use App\Models\Region\State;
use \App\Models\Region\Country as RegionCountry;

/**
 * Class Country
 * @package App\Models
 */
class Country extends BaseModel
{
    public const UNITED_STATES_ID  = 223;
    public const UNITED_KINGDOM_ID = 222;
    public const FRANCE_ID         = 73;

    /**
     * @var string
     */
    protected $primaryKey = 'countries_id';

    /**
     * @var array
     */
    protected $maps = [
        'id'    => 'countries_id',
        'name'  => 'countries_name',
        'iso_2' => 'countries_iso_code_2',
        'iso_3' => 'countries_iso_code_3',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'iso_numeric',
        'iso_2',
        'iso_3',
        'address_form_id',
        'calling_code',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'name',
        'iso_2',
        'iso_3',
    ];

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $iso_numeric;

    /**
     * @var int
     */
    protected $address_form_id;

    /**
     * @var int
     */
    protected $calling_code;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $iso_2;

    /**
     * @var string
     */
    protected $iso_3;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function prospect()
    {
        return $this->belongsTo(Prospect::class, 'pCountry', 'countries_id');
    }

    public function states()
    {
        return $this->hasManyThrough(
            State::class,
            RegionCountry::class,
            'll_country_id',
            'country_id',
            'countries_id'
        );
    }

    /**
     * Sometimes external sources pass us a country and we don't know if it is ISO2, ISO3, or
     * a full country string. This method will find a model regardless of which one it is.
     *
     * @param $countryStr
     * @return static
     * @throws \App\Exceptions\Address\UnrecognizedCountryException
     */
    public static function findFromUnknownSource($countryStr): self
    {
        // Determine which column to search based on length of input
        switch (strlen(trim($countryStr))) {
            case 3  : $col = 'countries_iso_code_3'; break;
            case 2  : $col = 'countries_iso_code_2'; break;
            default : $col = 'countries_name'      ; break;
        }

        // Check column for input value
        if ($model = self::where($col, $countryStr)->first()) {
            return $model;
        }

        throw new UnrecognizedCountryException();
    }
}
