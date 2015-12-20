<?php
/**
 * @project     Yii2 Bootstrap Daterangepicker
 * @filename    MomentJSAsset.php
 * @author      Mirdani Handoko <mirdani.handoko@gmail.com>
 * @copyright   copyright (c) 2011-2014, Mirdani Handoko
 * @license     Non-Freeware (Non Free Software License)
 */

namespace mdscomp\widget\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Class MomentJSAsset
 */
class MomentJSAsset extends AssetBundle {
	public $sourcePath = '@bower/moment';
	public $js         = [
		'min/moment.min.js',
		//'locales.min.js',
	];

	/**
	 * @var string|null When null, language will be equal for current locale of the application
	 */
	public $language = null;

	public static function register($view) {
		MomentJSAsset::registerAssetFiles($view);
		parent::register($view);
		//return $view->registerAssetBundle(get_called_class());
	}


	public function registerAssetFiles($view) {
		parent::registerAssetFiles($view);
		$language = $this->language ? $this->language : \Yii::$app->language;
		$this->registerLanguage($language, $view);
	}

	/**
	 * @param string $language
	 * @param View   $view
	 */
	public function registerLanguage($language, $view) {
		if (file_exists($this->sourcePath."/locale/{$language}.js")) {
			$this->js = array_merge($this->js, ['min/locales.min.js']);
			$view->registerJsFile($this->baseUrl."/locale/{$language}.js");
			$js = <<<JS
moment.locale('{$language}');
JS;
			$view->registerJs($js, View::POS_READY, 'moment-locale-'.$language);
		}
	}
}