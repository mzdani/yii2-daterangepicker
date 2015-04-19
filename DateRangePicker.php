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
use mdscomp\widget\assets\DateRangePickerAsset;
use mdscomp\widget\assets\MomentJSAsset;

/**
 * Class DateRangePicker
 */
class DateRangePicker extends InputWidget {
	/**
	 * @var string
	 */
	public $format;
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
	public $showDropdowns = false;
	/**
	 * @var bool
	 */
	public $showWeekNumbers = false;
	/**
	 * @var bool
	 */
	public $timePicker = false;
	/**
	 * @var int
	 */
	public $timePickerIncrement;
	/**
	 * @var int
	 */
	public $timePickerSeconds;
	/**
	 * @var bool
	 */
	public $timePicker12Hour = false;
	/**
	 * @var bool
	 */
	public $singleDatePicker = false;
	/**
	 * @var array
	 */
	public $defaultRanges = false;
	/**
	 * @var string
	 */
	public $language = '';
	/**
	 * @var array the options for the underlying js widget.
	 */
	public $clientOptions = [];
	/**
	 * @var string js for callback function.
	 */
	public $callback = false;
	/**
	 * @var string
	 */
	public $class;

	/**
	 * @var null|string
	 */
	public $onHide       = null;
	public $onShow       = null;
	public $onApply      = null;
	public $onCancel     = null;
	public $showCalendar = null;
	public $hideCalendar = null;
	public $clearInput   = true;

	public function init() {
		parent::init();
		if ($this->language === null) {
			$this->language = Yii::$app->language;
		}
	}

	public function run() {
		echo $this->renderWidget()."\n";
		DateRangePickerAsset::register($this->view);
		$containerID = $this->options['id'];

		$this->clientOptions['format']           = $this->format;
		$this->clientOptions['showDropdowns']    = $this->showDropdowns;
		$this->clientOptions['showWeekNumbers']  = $this->showWeekNumbers;
		$this->clientOptions['singleDatePicker'] = $this->singleDatePicker;
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
		$this->registerEvents($containerID);
	}

	protected function renderWidget() {
		if ($this->hasModel()) {
			$value = Html::getAttributeValue($this->model, $this->attribute);
		} else {
			$value = $this->value;
		}
		$options          = array_merge($this->options, [
			'class'       => 'form-control',
			'placeholder' => 'Start Date - End Date'
		]);
		$options['value'] = $value;

		$contents[] = '<div class="row"><div class="col-xs-6 col-xs-offset-3">';
		$contents[] = '<div class="input-group field-'.(($this->hasModel()) ? $this->attribute : $this->name).'">';
		if ($this->hasModel()) {
			$contents[] = Html::activeTextInput($this->model, $this->attribute, $options);
			if (!$this->callback) {
				$contents[] = Html::hiddenInput($this->model, $this->options['id'].'-start', ['id' => $this->options['id'].'-start']);
				$contents[] = Html::hiddenInput($this->model, $this->options['id'].'-end', ['id' => $this->options['id'].'-end']);
			}
		} else {
			$contents[] = '<span class="input-group-addon"><i class="fa fa-calendar"></i></span>'.Html::textInput($this->name, $value, $options);
			if (!$this->callback) {
				$contents[] = Html::hiddenInput($this->options['id'].'-start', null, ['id' => $this->options['id'].'-start']);
				$contents[] = Html::hiddenInput($this->options['id'].'-end', null, ['id' => $this->options['id'].'-end']);
			}
		}
		$contents[] = '</div></div></div>';

		return implode("\n", $contents);
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
			'customRangeLabel' => Yii::t('mdscomp/daterangepicker', 'Custom', [], $this->language),
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
			$callback = (!$this->callback) ? new JsExpression('function(start, end) { jQuery("#'.$id.'-start").val(start.format("YYYY-MM-DD")); jQuery("#'.$id.'-end").val(end.format("YYYY-MM-DD"));}') : new JsExpression($this->callback);
			$js       = "jQuery('#$id').$name($options".(($callback !== '') ? ', '.$callback : '').");";
			$this->getView()->registerJs($js);
		}
	}

	protected function getInputId($model, $attribute){
		return Html::getInputId($model, $attribute);
	}

	protected function registerEvents($id) {
		$events = '';
		if ($this->onShow !== null) {
			$events .= $this->onShow($id);
		}
		if ($this->onHide !== null) {
			$events .= $this->onHide($id);
		}
		if ($this->showCalendar !== null) {
			$events .= $this->showCalendar($id);
		}
		if ($this->hideCalendar !== null) {
			$events .= $this->hideCalendar($id);
		}
		if ($this->onApply !== null) {
			$events .= $this->onApply($id);
		}
		if ($this->onCancel !== null) {
			$this->clearInput = false;
			$events .= $this->onCancel($id);
		}
		if ($this->clearInput) {
			$events .= $this->clearInput($id);
		}
		if ($events !== '') {
			$js = new JsExpression($events);
			$this->getView()->registerJs($js);
		}
	}

	protected function onShow() {
		$js = 'jQuery(\'#'.$id.'\').on(\'onShow.daterangepicker\', function(ev, picker) { '.$this->onShow.' });';

		return $js;
	}

	protected function onHide() {
		$js = 'jQuery(\'#'.$id.'\').on(\'onHide.daterangepicker\', function(ev, picker) { '.$this->onHide.' });';

		return $js;
	}

	protected function showCalendar() {
		$js = 'jQuery(\'#'.$id.'\').on(\'showCalendar.daterangepicker\', function(ev, picker) { '.$this->showCalendar.' });';

		return $js;
	}

	protected function hideCalendar() {
		$js = 'jQuery(\'#'.$id.'\').on(\'hideCalendar.daterangepicker\', function(ev, picker) { '.$this->hideCalendar.' });';

		return $js;
	}

	protected function onApply($id) {
		$js = 'jQuery(\'#'.$id.'\').on(\'apply.daterangepicker\', function(ev, picker) { '.$this->onApply.' });';

		return $js;
	}

	protected function onCancel($id) {
		$js = 'jQuery(\'#'.$id.'\').on(\'cancel.daterangepicker\', function(ev, picker) { '.$this->onCancel.' });';

		return $js;
	}

	protected function clearInput($id) {
		$js = 'jQuery(\'#'.$id.'\').on(\'cancel.daterangepicker\', function(ev, picker) { jQuery(\'#'.$this->options['id'].'\').val(\'\'); jQuery(\'#'.$this->options['id'].'-start\').val(\'\'); jQuery(\'#'.$this->options['id'].'-end\').val(\'\'); });';

		return $js;
	}

}