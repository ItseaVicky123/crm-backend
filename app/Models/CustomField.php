<?php

namespace App\Models;

use App\Exceptions\CustomModelException;
use App\Exceptions\CustomFieldValueTypeMismatchException;
use App\Exceptions\CustomFieldSegments\CustomFieldSegmentTypeException;
use App\Exceptions\CustomFieldSegments\CustomFieldSegmentWidgetTypeException;
use App\Exceptions\CustomFieldSegments\CustomFieldSegmentWidgetOperationException;
use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Class CustomField
 * @package App\Models
 */
class CustomField extends Model
{
    use SoftDeletes, Mappable, Eloquence;

    const MAX_FIELDS = 250;

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var array
     */
    protected $dates = ['deleted_at', 'created_at', 'updated_at'];

    /**
     * @var array
     */
    protected $fillable = [
        'entity_type_id',
        'field_type_id',
        'name',
        'is_multi',
        'created_by',
        'updated_by',
        'entity_id',
        'token_key',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'field_type_id',
        'name',
        'is_multi',
        'options',
        'type_id',
        'email_token_name',
        'email_token_description',
        'token_key',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'options',
        'type_id',
        'entity_id',
    ];

    /**
     * @var array
     */
    protected $map = [
        'type_id' => 'entity_type_id',
    ];

    /**
     * @var array
     */
    protected $searchableColumns = [
        'id',
        'name',
        'type.name',
        'field_type.name',
    ];

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $entity_type_id;

    /**
     * @var int
     */
    protected $field_type_id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $token_key;

    /**
     * @var int
     */
    protected $created_by;

    /**
     * @var int
     */
    protected $updated_by;

    /**
     * @var int
     */
    protected $entity_id;

    /**
     * @var null
     */
    protected $options;

    // Dates
    //
    protected $created_at;
    protected $updated_at;
    protected $deleted_at;

    protected $perPage = 100;

    /**
     * @var bool
     */
    private $isInternalRequest = false;

    // Conversion to segments service types
    private $segmentMap = [
        Contact::ENTITY_ID => 34,
    ];

    private $segmentWidgetMap = [
        CustomFieldType::TYPE_TEXT        => 1,
        CustomFieldType::TYPE_NUMERIC     => 1,
        CustomFieldType::TYPE_DATE        => 5,
        CustomFieldType::TYPE_BOOLEAN     => 4,
        CustomFieldType::TYPE_ENUMERATION => [
            2,
            3
        ],
    ];

    private $segmentOperationMap = [
       CustomFieldType::TYPE_TEXT        => [1, 2, 5, 6],
       CustomFieldType::TYPE_NUMERIC     => [1, 2, 7, 8, 9, 10],
       CustomFieldType::TYPE_DATE        => [1, 2, 3, 4],
       CustomFieldType::TYPE_BOOLEAN     => [1],
       CustomFieldType::TYPE_ENUMERATION => [1, 2],
    ];

    /**
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim($value);
    }

    /**
     * @param $value
     * @throws CustomModelException
     */
    public function setTokenKeyAttribute($value)
    {
        if (! $value) {
            $value  = strtolower(preg_replace('/\s+/', '_', $this->getAttribute('name')));
            $exists = CustomField::where('entity_type_id', $this->getAttribute('entity_type_id'))
                ->where('token_key', $value)
                ->first();

            if ($exists != null) {
                $key = 'custom-fields.token-key-exists';

                throw new CustomModelException($key, ['token_key' => 'A custom field for this type already has a field with the token key that would be auto-generated. Please supply a custom token key or update the name of the field']);
            }
        }

        $this->attributes['token_key'] = $value;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function field_type()
    {
        return $this->hasOne(CustomFieldType::class, 'id', 'field_type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function type()
    {
        return $this->hasOne(EntityType::class, 'id', 'entity_type_id');
    }

    /**
     * @param Builder $query
     * @param int $type_id
     * @return Builder
     */
    public function scopeOfEntityType(Builder $query, int $type_id)
    {
        return $query->where('entity_type_id', $type_id);
    }

    /**
     * @param int $type_id
     * @return bool
     */
    public function isOfFieldType(int $type_id)
    {
        return $this->getAttribute('field_type_id') == $type_id;
    }

    /**
     * @param int $type_id
     * @return bool
     */
    public function isOfEntityType($type_id)
    {
        if (is_array($type_id)) {
            foreach ($type_id as $id) {
                if ($this->isOfEntityType($id)) {
                    return true;
                }
            }

            return false;
        } else {
            return $this->getAttribute('entity_type_id') == $type_id;
        }
    }

    /**
     * @return mixed
     */
    public function getTypeAttribute()
    {
        return ucfirst(Str::singular(EntityType::findOrFail($this->attributes['entity_type_id'])->table_name));
    }

    /**
     * @return mixed
     */
    public function getTypeNameAttribute()
    {
        return $this->getTypeAttribute();
    }

   /**
    * @return mixed
    */
    public function getFieldTypeNameAttribute()
    {
        return $this->field_type()->first()->name;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function options()
    {
        return $this->hasMany(CustomFieldOption::class);
    }

    /**
     *  @return mixed
     */
    public function getOptionsAttribute()
    {
        if (!$this->options) {
            $options = CustomFieldOption::where('custom_field_id', '=', $this->getAttribute('id'));

            if ($this->isInternalRequest) {
                $this->options = $options
                    ->withTrashed()
                    ->get()
                    ->each(function ($option) {
                        $option->makeVisible(['deleted_at']);
                    });
            } else {
                $this->options = $options->get();
            }
        }

        return $this->options;
    }

    /**
     * @return int
     */
    public function getTypeIdAttribute()
    {
        return ($this->attributes['entity_type_id'] ?: 0);
    }

    /**
     * @param $id
     */
    public function setEntityIdAttribute($id)
    {
        $this->attributes['entity_id'] = $id;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function values()
    {
        $entity_type_id = $this->getAttribute('entity_type_id');

        if (in_array($entity_type_id, [Prospect::ENTITY_ID, Customer::ENTITY_ID])) {
            $entity_type_id = Contact::ENTITY_ID;
        }

        return $this->hasMany(CustomFieldValue::class, 'custom_field_id', 'id')
            ->where('entity_id', $this->getAttribute('entity_id'))
            ->where('entity_type_id', $entity_type_id);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getValuesAttribute()
    {
        return $this->values()->get();
    }

    /**
     * @return int
     */
    public function getEntityIdAttribute()
    {
        return (isset($this->attributes['entity_id']) ? $this->attributes['entity_id'] : 0);
    }

    /**
     * @param Request $request
     * Determine if this is an internal API request
     * User will be internal and ApiUser is external
     * @return $this
     */
    public function isInternalRequest(Request $request)
    {
        $this->isInternalRequest = ($request->user() instanceof User);

        return $this;
    }

    /**
     * @return string
     */
    public function getEmailTokenNameAttribute()
    {
        return strtolower("{{$this->type()->first()->name}.{$this->getAttribute('token_key')}}");
    }

    /**
     * @return string
     */
    public function getEmailTokenDescriptionAttribute()
    {
        return "{$this->type()->first()->name} {$this->getAttribute('name')}";
    }

    /**
     * @param $value
     * @throws CustomFieldValueTypeMismatchException
     */
    public function validateValueType($value)
    {
        switch ($this->getAttribute('field_type_id')) {
            case CustomFieldType::TYPE_NUMERIC:
                if (! is_numeric($value)) {
                    throw new CustomFieldValueTypeMismatchException('Value must be numeric');
                }
            break;
            case CustomFieldType::TYPE_DATE:
                if (! preg_match('~\d{4}-\d{2}-\d{2}~', $value) || ! Carbon::parse($value)) {
                    throw new CustomFieldValueTypeMismatchException('Value must be a date of format YYYY-MM-DD');
                }
            break;
            case CustomFieldType::TYPE_BOOLEAN:
                if (! in_array($value, [0,1])) {
                    throw new CustomFieldValueTypeMismatchException('Value must be boolean');
                }
        }
    }

    public function getSegmentCriterionId() {
        if (! isset($this->segmentMap[$this->getAttribute('entity_type_id')])) {
            throw new CustomFieldSegmentTypeException('Invalid segment type mapping');
        }

        return $this->segmentMap[$this->getAttribute('entity_type_id')];
    }

    public function getSegmentWidgetType() {
        if (! isset($this->segmentWidgetMap[$this->getAttribute('field_type_id')])) {
            throw new CustomFieldSegmentWidgetTypeException('Invalid segment widget type mapping');
        }

        if ($mapping = $this->segmentWidgetMap[$this->getAttribute('field_type_id')] ) {
            if (is_array($mapping)) {
                return $mapping[(int) $this->is_multi];
            }
        }

        return $mapping;
    }

    public function getSegmentOperations() {
        if (! isset($this->segmentOperationMap[$this->getAttribute('field_type_id')])) {
            throw new CustomFieldSegmentWidgetOperationException('Invalid segment widget operation mapping');
        }

        return $this->segmentOperationMap[$this->getAttribute('field_type_id')];
    }
}
