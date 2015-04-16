<?php
/**
 * @project     Yii2 Bootstrap Daterangepicker
 * @filename    DateRangePickerAsset.php
 * @author      Mirdani Handoko <mirdani.handoko@gmail.com>
 * @copyright   copyright (c) 2011-2014, Mirdani Handoko
 * @license     Non-Freeware (Non Free Software License)
 */

namespace mdscomp\widget;

use yii\web\AssetBundle;

/**
 * Class DateRangePickerAsset
 */
class DateRangePickerAsset extends AssetBundle {
	public $sourcePath = '@bower/bootstrap-daterangepicker';
	public $js         = [
		'daterangepicker.js'
	];
	public $css        = [
		'daterangepicker-bs3.css'
	];
	public $depends    = [
		'mdscomp\widget\MomentJSAsset',
		'yii\web\JqueryAsset',
	];
}