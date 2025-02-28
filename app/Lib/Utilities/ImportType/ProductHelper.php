<?php


namespace App\Lib\Utilities\ImportType;


use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDescription;
use Illuminate\Support\Facades\Log;
use rule_validator;


/**
 * Class ProductHelper
 * @package App\Lib\Utilities\ImportType
 */
class ProductHelper extends BaseTypeHelper
{
    const PRIMARY_KEY = 'product_id';

    /**
     * @var array|string[]
     */
    public array $validExtensions = [
        'csv',
    ];

    /**
     * @var int
     */
    public int $threshold = 50;

    /**
     * @var int
     */
    public int $maxThreshold = 500;

    /**
     * @var array
     */
    public array $columnAliases = [];

    /**
     * @var array|string[]
     */
    public array $outputHeaders = [
        'product_id',
        'name',
        'description',
        'sku',
        'category_id',
        'price',
        'cost_of_goods_sold',
        'restocking_fee',
        'collections',
        'signature_confirmation',
        'delivery_confirmation',
        'taxable',
        'single_purchase_limit',
        'shippable',
        'digital_download',
        'digital_download_url',
        'max_quantity',
        'success',
        'status_message'
    ];

    /**
     * @var array|string[]
     */
    public array $columnMap = [
        'name',
        'description',
        'sku',
        'category_id',
        'price',
        'cost_of_goods_sold',
        'restocking_fee',
        'collections',
        'signature_confirmation',
        'delivery_confirmation',
        'taxable',
        'single_purchase_limit',
        'shippable',
        'digital_download',
        'digital_download_url',
        'max_quantity',
    ];

    /**
     * @var array|string[]
     */
    public array $optionalColumns = [
        'description',
        'cost_of_goods_sold',
        'restocking_fee',
        'collections',
        'signature_confirmation',
        'delivery_confirmation',
        'digital_download_url',
        'max_quantity',
    ];

    /**
     * @var array
     */
    protected array $descriptionRecord;

    /**
     * @var Product|null
     */
    protected ?Product $newModel;

    /**
     * @var array
     */
    protected array $defaultOutput = [
        'product_id'             => 0,
        'name'                   => '',
        'description'            => '',
        'sku'                    => '',
        'category_id'            => 0,
        'price'                  => 0,
        'cost_of_goods_sold'     => 0,
        'restocking_fee'         => 0,
        'collections'            => 0,
        'signature_confirmation' => 0,
        'delivery_confirmation'  => 0,
        'taxable'                => 0,
        'single_purchase_limit'  => 0,
        'shippable'              => 0,
        'digital_download'       => 0,
        'digital_download_url'   => '',
        'max_quantity'           => 0,
        'success'                => 0,
        'status_message'         => '',
    ];

    /**
     * @return bool
     */
    public function saveRecord(): bool
    {
        $success = false;

        if ((bool) $this->record) {
            $this->newModel = new Product($this->record);
            $saved          = $this->newModel->save();

            if ($saved) {
                $this->newModel = $this->newModel->fresh();
                $this->appendPrimaryKey();
                $success = $this->afterSave();
                if (!$success) {
                    Log::debug(__FUNCTION__ . " - Saved product but failed to save product description");
                }
            } else {
                Log::debug(__FUNCTION__ . " - Unable to save new product");
            }
        }

        return $success;
    }

    /**
     * @param $data
     */
    protected function validateData($data): void
    {
        $this->ruleValidator->validate_catch_error($data);
    }

    /**
     * @return void
     */
    protected function appendPrimaryKey(): void
    {
        $this->lastRow[self::PRIMARY_KEY] = $this->newModel->products_id;
    }

    /**
     * @return bool
     */
    protected function afterSave(): bool
    {
        $this->newModel->categories()->attach($this->newModel->master_categories_id);

        if ($this->descriptionRecord) {
            $productDescription = new ProductDescription($this->descriptionRecord);
            if (!$this->newModel->meta()->save($productDescription)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array
     */
    protected function getRules(): array
    {
        return [
            'name'                   => $this->commonRules['required_text'],
            'description'            => rule_validator::rule_maxlength(65535),
            'sku'                    => $this->commonRules['required_text'],
            'category_id'            => rule_validator::rule_primary_key(),
            'price'                  => array_merge([rule_validator::required()], $this->commonRules['money']),
            'cost_of_goods_sold'     => $this->commonRules['money'],
            'restocking_fee'         => $this->commonRules['money'],
            'collections'            => rule_validator::rule_flag(),
            'signature_confirmation' => rule_validator::rule_flag(),
            'delivery_confirmation'  => rule_validator::rule_flag(),
            'taxable'                => $this->commonRules['required_flag'],
            'single_purchase_limit'  => $this->commonRules['required_flag'],
            'shippable'              => $this->commonRules['required_flag'],
            'digital_download'       => $this->commonRules['required_flag'],
            'digital_download_url'   => rule_validator::rule_maxlength(255),
            'max_quantity'           => rule_validator::rule_int(),
        ];
    }

    /**
     * @param $data
     * @return bool
     */
    protected function buildRecord($data): bool
    {
        $this->record = [
            // csv defined fields
            'sku'                           => $data->sku,
            'price'                         => $data->price,
            'cost_of_goods'                 => $data->get_notempty('cost_of_goods_sold', 0),
            'restocking_fee'                => $data->get_notempty('restocking_fee', 0),
            'is_collections_enabled'        => $data->get_notempty('collections', 0),
            'is_trial_product'              => $data->single_purchase_limit,
            'signature_confirmation'        => $data->get_notempty('signature_confirmation', 0),
            'delivery_confirmation'         => $data->get_notempty('delivery_confirmation', 0),
            'is_taxable'                    => $data->taxable,
            'is_shippable'                  => $data->shippable,
            'digital_delivery_URL'          => $data->get_notempty('digital_download_url'),
            'max_quantity'                  => $data->get_notempty('max_quantity', 1),
            'master_categories_id'          => (int) Category::where('active', 1)->first(),

            // Undefined defaults
            'products_price_sorter'         => $data->price,
            'products_status'               => 1,
            'manufacturers_id'              => 0,
            'products_quantity_order_min'   => 0,
            'products_quantity_order_units' => 0,
            'products_qty_box_status'       => 0,
            'subscription_type'             => 0,
            'recurring_discount_max'        => null,
        ];

        $this->descriptionRecord = [
            'name'            => $data->name,
            'description'     => $data->get_notempty('description'),
        ];

        return true;
    }
}