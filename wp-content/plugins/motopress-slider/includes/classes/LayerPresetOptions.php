<?php

/** @todo: Maybe make preview styles like in MPCE (transient OR parent.window variable) */

require_once dirname(__FILE__) . '/ChildOptions.php';

class MPSLLayerPresetOptions extends MPSLChildOptions {
	private static $instance = null;
	private $defaults = null;
	private $defaultPresets = null;
	private $presets = null;
	private $lastPresetId = 0;
	private $lastPrivatePresetId = 0;
	private $preview = false;

	const PRESETS_OPT = 'mpsl_preset';
	const CSS_OPT = 'mpsl_css';
	const DEFAULT_CSS_OPT = 'mpsl_default_css';
	const PREVIEW_CSS_OPT = 'mpsl_preview_css';
	const PREVIEW_DEFAULT_CSS_OPT = 'mpsl_preview_default_css';
	const PRIVATE_CSS_OPT = 'mpsl_private_css';
	const PRIVATE_PREVIEW_CSS_OPT = 'mpsl_private_preview_css';
	const LAST_PRESET_ID_OPT = 'mpsl_last_preset_id';
	const LAST_PRIVATE_PRESET_ID_OPT = 'mpsl_last_private_preset_id';
	const PRESET_PREFIX = 'mpsl-preset-';
	const PRIVATE_PRESET_PREFIX = 'mpsl-private-preset-';
	const LAST_PRESET_ID_DEFAULT = 0;
	const LAYER_CLASS = 'mpsl-layer';
	const LAYER_HOVER_CLASS = 'mpsl-layer-hover';

	function __construct($preview = false) {
		parent::__construct();

		$this->preview = $preview;

		$this->lastPresetId = get_option(self::LAST_PRESET_ID_OPT, self::LAST_PRESET_ID_DEFAULT);
		$this->lastPrivatePresetId = get_option(self::LAST_PRIVATE_PRESET_ID_OPT, self::LAST_PRESET_ID_DEFAULT);

		$this->defaultPresets = include($this->pluginDir . 'defaults/style-presets/presets.php');

		$this->options = include($this->getSettingsPath());
		$this->prepareOptions($this->options);

		$this->defaults = $this->getDefaults($this->options);

		$loaded = $this->load();

		if (!$loaded) {
			// TODO: Throw error
//            _e('Record not found', 'motopress-slider');
		}
	}

	public static function getInstance($preview = false) {
		if (null === self::$instance) {
			self::$instance = new self($preview);
		}
		return self::$instance;
	}

	protected function load() {
		$this->defaultPresets = $this->override($this->defaultPresets, false, true);
		$this->override(get_option(self::PRESETS_OPT, array()));
		return true;
	}

	public function override($presets = null, $single = false, $silent = false) {
		if (!empty($presets)) {
			if ($single) {
				if (!is_array($presets)) $presets = array();
				$presets = array_replace_recursive($this->defaults, $presets);
				$presets['hover']['allow_style'] = $presets['settings']['hover'];
			} else {
				foreach ($presets as $presetKey => $preset) {
					if (!is_array($presets[$presetKey])) $presets[$presetKey] = array();
					$presets[$presetKey] = array_replace_recursive($this->defaults, $presets[$presetKey]);
					$presets[$presetKey]['hover']['allow_style'] = $presets[$presetKey]['settings']['hover'];
				}
			}
		}

		if (!$single && !$silent) $this->presets = $presets;

		return $presets;
	}

	public function update() {
		if (!$this->preview) {
			update_option(self::LAST_PRESET_ID_OPT, $this->getLastPresetId());
			update_option(self::LAST_PRIVATE_PRESET_ID_OPT, $this->getLastPrivatePresetId());

			$cleanPresets = $this->clearPresets($this->presets);
			update_option(self::PRESETS_OPT, $cleanPresets);
		}

		$defaultCss = $this->compile($this->defaultPresets);
		$css = $this->compile($this->presets);

		if ($defaultCss !== false && is_string($defaultCss)) update_option($this->preview ? self::PREVIEW_DEFAULT_CSS_OPT : self::DEFAULT_CSS_OPT, $defaultCss);
		if ($css !== false && is_string($css)) update_option($this->preview ? self::PREVIEW_CSS_OPT : self::CSS_OPT, $css);

		return true;
	}

	public function render() {
		global $mpsl_settings;
		include($this->getViewPath());
	}

	public function getDefaultPresets() {
		return $this->defaultPresets;
	}

	public function getPresets() {
		return $this->presets;
	}

	public function getAllPresets() {
		return array_merge($this->defaultPresets, $this->presets);
	}

	public function setPresets($presets) {
		return $this->presets = $presets;
	}

	public function getDefaults(&$options = array()) {
		$defaults = parent::getDefaults($options);
		return array(
			'style' => $defaults,
			'hover' => $defaults,
			'settings' => array(
				'label' => '',
				'hover' => true
			),
		);
	}

	public function getOptionsDefaults($settingsFileName = false) {
		$defaults = parent::getOptionsDefaults($settingsFileName);
		return array(
			'style' => $defaults,
			'hover' => $defaults,
			'settings' => array(
				'label' => '',
				'hover' => true
			),
		);
	}

	protected function getSettingsFileName() {
		return 'preset';
	}

	protected function getViewFileName() {
		return 'preset';
	}

	/*public function setUniqueName(&$preset) {
		// TODO: Generate Name
	}*/

	public function getLastPresetId() {
		return $this->lastPresetId;
	}

	public function setLastPresetId($id) {
		if (is_numeric($id)) $this->lastPresetId = $id;
	}

	public function incLastPresetId() {
		$this->lastPresetId ++;
	}

	public function getLastPresetClass() {
		return self::PRESET_PREFIX . $this->getLastPresetId();
	}

	public function getLastPrivatePresetId() {
		return $this->lastPrivatePresetId;
	}

	public function setLastPrivatePresetId($id) {
		if (is_numeric($id)) $this->lastPrivatePresetId = $id;
	}

	public function incLastPrivatePresetId() {
		$this->lastPrivatePresetId ++;
	}

	public function getLastPrivatePresetClass() {
		return self::PRIVATE_PRESET_PREFIX . $this->getLastPrivatePresetId();
	}

	public function compile($presets, $prepare = false, $separated = false) {
		if ($prepare) $presets = $this->override($presets, false, true);
		$options = $this->getOptions(false); // TODO: Get options on init
		$css = '';
		$cssArr = array();

		foreach ($presets as $class => $preset) {
			if (!$this->isValidPreset($preset)) continue;

			$types = array('style');
			if (!isset($preset['settings']['hover']) || $preset['settings']['hover']) {
				$types[] = 'hover';
			}
			if ($separated) $css = '';

			foreach ($types as $type) {
				// Add cross-browser options
				foreach ($preset[$type] as $optName => $optVal) {
					if (!array_key_exists($optName, $options)) continue;
					switch ($optName) {
						case 'border-radius':
							$options['-moz-' . $optName] = $options['-webkit-' . $optName] = $options[$optName];
							unset($preset[$type][$optName]);
							$preset[$type][$optName] = $preset[$type]['-moz-' . $optName] = $preset[$type]['-webkit-' . $optName] = $optVal;
							break;
					}
				}

				$css .= '.' . self::LAYER_CLASS . ".$class";
				if ($type === 'hover') {
					$css .= $separated ? ('.' . self::LAYER_HOVER_CLASS) : ":hover";
				}
				$css .= "{";
				foreach ($preset[$type] as $optName => $optVal) {
					if (!array_key_exists($optName, $options)) continue;
					if ($options[$optName]['isChild']) continue;

//					switch ($optName) {
//						case 'font-style':
//							$optVal = $optVal ? 'italic' : '';
//							break;
						/*case 'line-height':
							$optVal = $optVal === '' ? 'normal' : $optVal;
							break;*/
//					}

					// Skip empty & helper options
					if (!is_string($optVal) || $optVal === '' || in_array($optName, array('allow_style', 'custom_styles'))) continue;

					// Add unit
					$css .= $optName . ':' . trim($optVal);
					if (is_numeric($optVal) && $unit = $options[$optName]['unit']) $css .= $unit;
					$css .= ';';
				}
				// Remove line breaks
				if (array_key_exists('custom_styles', $preset[$type])) {
					$css .= preg_replace('/\s+/S', " ", $preset[$type]['custom_styles']);
				}
				$css .= "}";
			}

			$cssArr[$class] = $css;
		}

		return $separated ? $cssArr : $css;
	}

	public function updatePrivateStyles($previewSlideId = null) {
		$db = MPSliderDB::getInstance();
		$slides = $db->getSlideList(array('id', 'layers'));
		$privateStyleList = array();

	    foreach ($slides as $slide) {
			if (!$slide || empty($slide)) continue;

		    // Get preview slide
		    if ($this->preview && $slide['id'] == $previewSlideId) {
			    $previewSlide = $db->getPreviewSlide($slide['id'], array('id', 'layers'));
			    if (!$previewSlide || empty($previewSlide)) continue;
			    $slide['layers'] = json_decode($previewSlide['layers'], true);
			    if (!is_array($slide['layers'])) $slide['layers'] = array();
		    }

		    foreach ($slide['layers'] as &$layer) {
			    if (isset($layer['preset']) && $layer['preset'] === 'private') {
				    if (isset($layer['private_preset_class']) && $layer['private_preset_class']) {
					    $privateStyleList[$layer['private_preset_class']] = $layer['private_styles'];
				    }
			    }
		    }
	    }

	    $css = $this->compile($privateStyleList, true);
		if ($css !== false && is_string($css)) update_option($this->preview ? self::PRIVATE_PREVIEW_CSS_OPT : self::PRIVATE_CSS_OPT, $css);
	}

	public function loadNewPresets($newPresets) {
		$newClasses = array();
		foreach ($newPresets as $pClass => $preset) {
			if (preg_match('/^' . self::PRESET_PREFIX . '[0-9]+$/', $pClass)) {
				$this->incLastPresetId();
				$pClassNew = $this->getLastPresetClass();
				$newClasses[$pClass] = $pClassNew;
				$this->presets[$pClassNew] = $this->override($preset, true, true);
			}
		}
		return $newClasses;
	}

	public function clearPresets($presets = array()) {
		if (!is_array($presets)) return array();

		foreach ($presets as &$preset) {
			$preset = $this->clearPreset($preset);
		}

		return $presets;
	}

	public function clearPreset($preset) {
		if ($preset && $this->isValidPreset($preset)) {
			foreach (array('style', 'hover') as $mode) {
				if (!isset($preset[$mode])) continue;
				foreach ($preset[$mode] as $optKey => $optVal) {
					if ($optVal === '') {
						unset($preset[$mode][$optKey]);
					}
				}
			}
			if (isset($preset['settings']['label']) && !$preset['settings']['label']) {
				unset($preset['settings']['label']);
			}
			if (isset($preset['settings']['hover']) && $preset['settings']['hover']) {
				unset($preset['settings']['hover']);
			}
		}
		return $preset;
	}

	public function getFontsByPreset($preset) {
		$fonts = array();
		if (!$this->isValidPreset($preset)) return $fonts;

		$types = array('style');
		if ($preset['settings']['hover']) $types[] = 'hover';
		foreach ($types as $type) {
			if (isset($preset[$type]['font-family']) && $fontName = $preset[$type]['font-family']) {
				if (!array_key_exists($fontName, $fonts)) $fonts[$fontName] = array('variants' => array());
				if (($fontWeight = $preset[$type]['font-weight']) && !in_array($fontWeight, $fonts[$fontName]['variants'])) {
					// Normal
					$fontWeight = $fontWeight === 'normal' ? 'regular' : $fontWeight;
					$fonts[$fontName]['variants'][] = $fontWeight;

					// Italic
					if (isset($preset[$type]['font-style']) && $preset[$type]['font-style'] === 'italic') {
						$fontWeight = $fontWeight === 'regular' ? 'italic' : $fontWeight . 'italic';
						if (!in_array($fontWeight, $fonts[$fontName]['variants'])) {
							$fonts[$fontName]['variants'][] = $fontWeight;
						}
					}
				}
			}
		}
		return $fonts;
	}

	public function getDefaultPresetFonts() {
		$defaultFonts = array();
		if (count($this->defaultPresets)) {
			foreach ($this->defaultPresets as $defaultPreset) {
				$defaultFonts = array_merge_recursive($defaultFonts, $this->getFontsByPreset($defaultPreset));
			}
		}
		return self::fontsUnique($defaultFonts);
	}

	public function getPresetFonts() {
		$fonts = array();
		if (count($this->presets)) {
			foreach ($this->presets as $preset) {
				$fonts = array_merge_recursive($fonts, $this->getFontsByPreset($preset));
			}
		}
		return self::fontsUnique($fonts);
	}

	public function getAllPresetFonts() {
		$fonts = array_merge_recursive($this->getDefaultPresetFonts(), $this->getPresetFonts());
		return self::fontsUnique($fonts);
	}

	public function isValidPreset($preset) {
		return (
			is_array($preset) && !empty($preset) &&
			isset($preset['settings']) && is_array($preset['settings']) &&
			isset($preset['style']) && is_array($preset['style']) &&
			isset($preset['hover']) && is_array($preset['hover'])
		);
	}

	public static function fontsUnique($fonts) {
		foreach ($fonts as $fKey => $fVal) {
			$fonts[$fKey]['variants'] = array_values(array_unique($fVal['variants']));
		}
		return $fonts;
	}

	public static function getDefaultCss() {
		return wp_strip_all_tags(get_option(MPSLSharing::$isPreviewPage ? self::PREVIEW_DEFAULT_CSS_OPT : self::DEFAULT_CSS_OPT, ''));
	}

	public static function getCustomCss() {
		return wp_strip_all_tags(get_option(MPSLSharing::$isPreviewPage ? self::PREVIEW_CSS_OPT : self::CSS_OPT, ''));
	}

	public static function getPrivateCss() {
		return wp_strip_all_tags(get_option(MPSLSharing::$isPreviewPage ? self::PRIVATE_PREVIEW_CSS_OPT : self::PRIVATE_CSS_OPT, ''));
	}

	public static function getAllCss() {
		return self::getDefaultCss() . self::getCustomCss() . self::getPrivateCss();
	}

}