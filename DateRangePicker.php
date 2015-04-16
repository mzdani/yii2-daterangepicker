<?php
/**
 * @project     Yii2 Bootstrap Daterangepicker
 * @filename    DateRangePicker.php
 * @author      Mirdani Handoko <mirdani.handoko@gmail.com>
 * @copyright   copyright (c) 2015, Mirdani Handoko
 * @license     BSD-3-Clause
 */

namespace mdscomp\widget;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FormatConverter;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\widgets\InputWidget;
use mdscomp\widget\DateRangePickerAsset;
use mdscomp\widget\MomentJSAsset;

/**
 * Class DateRangePicker
 */
class DateRangePicker extends InputWidget {
	/**
	 * @var string
	 */
	public $dateFormat;
	/**
	 * @var string
	 */
	public $separator = ' - ';
	/**
	 * @var string
	 */
	public $opens = 'left';
	/**
	 * @var string
	 */
	public $drops = 'down';
	/**
	 * @var array
	 */
	public $buttonClasses = ['btn', 'btn-sm'];
	/**
	 * @var string
	 */
	public $applyClass = 'btn-primary';
	/**
	 * @var string
	 */
	public $cancelClass = 'btn-default';
	/**
	 * @var bool
	 */
	public $showDropdowns = true;
	/**
	 * @var bool
	 */
	public $showWeekNumbers = true;
	/**
	 * @var bool
	 */
	public $timePicker = false;
	/**
	 * @var bool
	 */
	public $timePicker12Hour = false;
	/**
	 * @var array
	 */
	public $defaultRanges = true;
	/**
	 * @var string
	 */
	public $language;
	/**
	 * @var array the options for the underlying js widget.
	 */
	public $clientOptions = [];
	/**
	 * @var string js for callback function.
	 */
	public $callback;

	public function init() {
		parent::init();
		if ($this->dateFormat === null) {
			$this->dateFormat = $this->timePicker ? Yii::$app->formatter->datetimeFormat : Yii::$app->formatter->dateFormat;
		}
		if ($this->language === null) {
			$this->language = Yii::$app->language;
		}
	}

	public function run() {
		echo $this->renderWidget()."\n";
		DateRangePickerAsset::register($this->view);
		$containerID = $this->options['id'];
		if (strncmp($this->dateFormat, 'php:', 4) === 0) {
			$format = substr($this->dateFormat, 4);
		} else {
			$format = FormatConverter::convertDateIcuToPhp($this->dateFormat, 'datetime', $this->language);
		}
		$this->clientOptions['format']           = $this->convertDateFormat($format);
		$this->clientOptions['showDropdowns']    = $this->showDropdowns;
		$this->clientOptions['showWeekNumbers']  = $this->showWeekNumbers;
		$this->clientOptions['timePicker']       = $this->timePicker;
		$this->clientOptions['timePicker12Hour'] = $this->timePicker12Hour;
		$this->clientOptions['opens']            = $this->opens;
		$this->clientOptions['drops']            = $this->drops;
		$this->clientOptions['buttonClasses']    = $this->buttonClasses;
		$this->clientOptions['applyClass']       = $this->applyClass;
		$this->clientOptions['cancelClass']      = $this->cancelClass;
		$this->clientOptions['separator']        = $this->separator;
		$this->setupRanges();
		$this->localize();
		$this->registerClientOptions('daterangepicker', $containerID);
	}

	protected function renderWidget() {
		if ($this->hasModel()) {
			$value = Html::getAttributeValue($this->model, $this->attribute);
		} else {
			$value = $this->value;
		}
		$options          = $this->options;
		$options['value'] = $value;
		if ($this->hasModel()) {
			$contents[] = Html::activeTextInput($this->model, $this->attribute, $options);
		} else {
			$contents[] = Html::textInput($this->name, $value, $options);
		}

		return implode("\n", $contents);
	}

	/**
	 * Automatically convert the date format from PHP DateTime to Moment.js DateTime format
	 * as required by bootstrap-daterangepicker plugin.
	 *
	 * @see    http://php.net/manual/en/function.date.php
	 * @see    http://momentjs.com/docs/#/parsing/string-format/
	 *
	 * @param string $format the PHP date format string
	 *
	 * @return string
	 * @author Kartik Visweswaran, Krajee.com, 2014
	 */
	protected static function convertDateFormat($format) {
		return strtr($format, [
			// meridian lowercase remains same
			// 'a' => 'a',
			// meridian uppercase remains same
			// 'A' => 'A',
			// second (with leading zeros)
			's' => 'ss',
			// minute (with leading zeros)
			'i' => 'mm',
			// hour in 12-hour format (no leading zeros)
			'g' => 'h',
			// hour in 12-hour format (with leading zeros)
			'h' => 'hh',
			// hour in 24-hour format (no leading zeros)
			'G' => 'H',
			// hour in 24-hour format (with leading zeros)
			'H' => 'HH',
			//  day of the week locale
			'w' => 'e',
			//  day of the week ISO
			'W' => 'E',
			// day of month (no leading zero)
			'j' => 'D',
			// day of month (two digit)
			'd' => 'DD',
			// day name short
			'D' => 'DDD',
			// day name long
			'l' => 'DDDD',
			// month of year (no leading zero)
			'n' => 'M',
			// month of year (two digit)
			'm' => 'MM',
			// month name short
			'M' => 'MMM',
			// month name long
			'F' => 'MMMM',
			// year (two digit)
			'y' => 'YY',
			// year (four digit)
			'Y' => 'YYYY',
			// unix timestamp
			'U' => 'X',
		]);
	}

	protected function setupRanges() {
		if ($this->defaultRanges && ArrayHelper::getValue($this->clientOptions, 'range') === null) {
			$this->clientOptions['ranges'] = [
				Yii::t('mdscomp/daterangepicker', 'Today', [], $this->language)        => new JsExpression('[new Date(), new Date()]'),
				Yii::t('mdscomp/daterangepicker', 'Yesterday', [], $this->language)    => new JsExpression('[moment().subtract("days", 1), moment().subtract("days", 1)]'),
				Yii::t('mdscomp/daterangepicker', 'Last 7 Days', [], $this->language)  => new JsExpression('[moment().subtract("days", 6), new Date()]'),
				Yii::t('mdscomp/daterangepicker', 'Last 30 Days', [], $this->language) => new JsExpression('[moment().subtract("days", 29), new Date()]'),
				Yii::t('mdscomp/daterangepicker', 'This Month', [], $this->language)   => new JsExpression('[moment().startOf("month"), moment().endOf("month")]'),
				Yii::t('mdscomp/daterangepicker', 'Last Month', [], $this->language)   => new JsExpression('[moment().subtract("month", 1).startOf("month"), moment().subtract("month", 1).endOf("month")]'),
			];
		}
	}

	protected function localize() {
		$this->clientOptions['locale'] = [
			'applyLabel'       => Yii::t('mdscomp/daterangepicker', 'Apply', [], $this->language),
			'cancelLabel'      => Yii::t('mdscomp/daterangepicker', 'Cancel', [], $this->language),
			'fromLabel'        => Yii::t('mdscomp/daterangepicker', 'From', [], $this->language),
			'toLabel'          => Yii::t('mdscomp/daterangepicker', 'To', [], $this->language),
			//'weekLabel'        => Yii::t('mdscomp/daterangepicker', 'W', [], $this->language),
			'customRangeLabel' => Yii::t('mdscomp/daterangepicker', 'Custom', [], $this->language),
			//'daysOfWeek'       => Yii::t('mdscomp/daterangepicker', 'Custom', [], $this->language),
			//'monthNames'       => Yii::t('mdscomp/daterangepicker', 'Custom', [], $this->language),
			'firstDay'         => 1,
		];
	}

	/**
	 * Registers a specific jQuery UI widget options
	 *
	 * @param string $name the name of the jQuery UI widget
	 * @param string $id   the ID of the widget
	 */
	protected function registerClientOptions($name, $id) {
		if ($this->clientOptions !== false) {
			$options  = empty($this->clientOptions) ? '' : Json::encode($this->clientOptions);
			$callback = empty($this->callback) ? '' : new JsExpression($this->callback);
			$js       = "jQuery('#$id').$name($options".(($callback !== '') ? ', '.$callback : '').");";
			$this->getView()->registerJs($js);
		}
	}
}