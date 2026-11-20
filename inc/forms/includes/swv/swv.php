<?php
/**
 * Schema-Woven Validation API
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use PaulusFormLibs\Swv\{ AbstractRule, CompositeRule };
use PaulusFormLibs\Swv\{ InvalidityException as Invalidity };
use PaulusFormLibs\FormDataTree\{ FormDataTreeInterface as FormDataTree };


/**
 * Returns an associative array of SWV rules.
 */
function paulus_form_swv_available_rules() {
	$rules = array(
		'all' => '\PaulusFormLibs\Swv\Rules\AllRule',
		'any' => '\PaulusFormLibs\Swv\Rules\AnyRule',
		'date' => '\PaulusFormLibs\Swv\Rules\DateRule',
		'dayofweek' => '\PaulusFormLibs\Swv\Rules\DayofweekRule',
		'email' => '\PaulusFormLibs\Swv\Rules\EmailRule',
		'enum' => '\PaulusFormLibs\Swv\Rules\EnumRule',
		'file' => '\PaulusFormLibs\Swv\Rules\FileRule',
		'maxdate' => '\PaulusFormLibs\Swv\Rules\MaxDateRule',
		'maxfilesize' => '\PaulusFormLibs\Swv\Rules\MaxFilesizeRule',
		'maxitems' => '\PaulusFormLibs\Swv\Rules\MaxItemsRule',
		'maxlength' => '\PaulusFormLibs\Swv\Rules\MaxLengthRule',
		'maxnumber' => '\PaulusFormLibs\Swv\Rules\MaxNumberRule',
		'mindate' => '\PaulusFormLibs\Swv\Rules\MinDateRule',
		'minfilesize' => '\PaulusFormLibs\Swv\Rules\MinFilesizeRule',
		'minitems' => '\PaulusFormLibs\Swv\Rules\MinItemsRule',
		'minlength' => '\PaulusFormLibs\Swv\Rules\MinLengthRule',
		'minnumber' => '\PaulusFormLibs\Swv\Rules\MinNumberRule',
		'number' => '\PaulusFormLibs\Swv\Rules\NumberRule',
		'required' => '\PaulusFormLibs\Swv\Rules\RequiredRule',
		'requiredfile' => '\PaulusFormLibs\Swv\Rules\RequiredFileRule',
		'stepnumber' => '\PaulusFormLibs\Swv\Rules\StepNumberRule',
		'tel' => '\PaulusFormLibs\Swv\Rules\TelRule',
		'time' => '\PaulusFormLibs\Swv\Rules\TimeRule',
		'url' => '\PaulusFormLibs\Swv\Rules\UrlRule',
	);

	return apply_filters( 'paulus_form_swv_available_rules', $rules );
}


/**
 * Creates an SWV rule object.
 *
 * @param string $rule_name Rule name.
 * @param string|array $properties Optional. Rule properties.
 * @return AbstractRule|null The rule object, or null if it failed.
 */
function paulus_form_swv_create_rule( $rule_name, $properties = '' ) {
	$properties = wp_parse_args( $properties );

	$rules = paulus_form_swv_available_rules();

	if ( isset( $rules[$rule_name] ) and class_exists( $rules[$rule_name] ) ) {
		return new $rules[$rule_name]( $properties );
	}
}


/**
 * Returns an associative array of JSON Schema for Forms SWV.
 */
function paulus_form_swv_get_meta_schema() {
	return array(
		'$schema' => 'https://json-schema.org/draft/2020-12/schema',
		'title' => 'Forms SWV',
		'description' => 'Forms SWV meta-schema',
		'type' => 'object',
		'properties' => array(
			'version' => array(
				'type' => 'string',
			),
			'locale' => array(
				'type' => 'string',
			),
			'rules' => array(
				'type' => 'array',
				'items' => array(
					'type' => 'object',
					'properties' => array(
						'rule' => array(
							'type' => 'string',
							'enum' => array_keys( paulus_form_swv_available_rules() ),
						),
						'field' => array(
							'type' => 'string',
							'pattern' => '^[A-Za-z][-A-Za-z0-9_:]*$',
						),
						'error' => array(
							'type' => 'string',
						),
						'accept' => array(
							'type' => 'array',
							'items' => array(
								'type' => 'string',
							),
						),
						'base' => array(
							'type' => 'string',
						),
						'interval' => array(
							'type' => 'number',
							'minimum' => 0,
						),
						'threshold' => array(
							'type' => 'string',
						),
					),
					'required' => array( 'rule' ),
				),
			),
		),
	);
}


/**
 * The schema class as a composite rule.
 */
final class PAULUS_FORM_SWV_Schema extends CompositeRule {

	/**
	 * The human-readable version of the schema.
	 */
	const version = 'SWV Core 2026-09-20';


	/**
	 * Rule properties.
	 */
	public readonly string $locale;


	/**
	 * Constructor.
	 *
	 * @param array $properties Rule properties.
	 */
	public function __construct( array $properties = [] ) {
		$this->locale = $properties[ 'locale' ] ?? '';
	}


	/**
 	 * Validates the form data according to the logic defined by this rule.
 	 *
 	 * @param FormDataTree $form_data Form data.
 	 * @param array $context Optional context.
 	 */
	public function validate( FormDataTree $form_data, array $context = [] ) {
		foreach ( $this->rules() as $rule ) {
			if ( $rule->matches( $context ) ) {
				try {
					$rule->validate( $form_data, $context );
				} catch ( Invalidity $error ) {
					yield new WP_Error( 'swv', $error->getMessage(), $error );
				}
			}
		}

		return true;
	}


	/**
	 * Returns an array that represents the rule properties.
	 *
	 * @return array Array of rule properties.
	 */
	public function toArray(): array {
		$rules_arrays = array_map( static function ( $item ) {
			return $item->toArray();
		}, $this->rules() );

		return array(
			'version' => self::version,
			'locale' => $this->locale,
			'rules' => $rules_arrays,
		);
	}


	/**
 	 * Wrapper function for addRule.
 	 *
     * @deprecated 6.2 Use addRule()
	 *
	 * @param AbstractRule $rule Sub-rule to be added.
 	 */
	public function add_rule( AbstractRule $rule ) {
		paulus_form_deprecated_function( __METHOD__, '6.2',
			'PAULUS_FORM_SWV_Schema::addRule'
		);

		return $this->addRule( $rule );
	}


	/**
	 * Wrapper function for toArray.
     *
     * @deprecated 6.2 Use toArray()
	 *
	 * @return array Array of rule properties.
	 */
	public function to_array() {
		paulus_form_deprecated_function( __METHOD__, '6.2',
			'PAULUS_FORM_SWV_Schema::toArray'
		);

		return $this->toArray();
	}

}
