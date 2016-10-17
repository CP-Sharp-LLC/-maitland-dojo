<?php

/** @var MPSLSliderOptions $this */
/** @var array $options */

$isAjax = defined('DOING_AJAX') && DOING_AJAX; // Checks `is it action ?`
//$isAction = isset($_POST['action']);
$isCreatePage = !(isset($_GET['id']) && $_GET['id']);
$optionsExists = isset($options) && is_array($options);

$categoriesArr = array();
$tagsArr = array();
$postTypesArr = array();
//$allPostTypesArr = array();
$postFormatsDependency = array();
$tagsDependency = array();
$catDependency = array();
$defaultPostType = $this->sliderType === 'post' ? 'post' : 'product';

// tmp
$_categories = array();
$_tags = array();
$_format = array();

if (($isCreatePage || $optionsExists) && !$isAjax && is_admin()) {

	if (in_array($this->sliderType, array('post', 'woocommerce'))) {

		if ($this->sliderType === 'post') {
			if ($optionsExists && isset($options['post_type']) && $options['post_type']) {
				$selectedPostType = $options['post_type'];
			} else {
				$selectedPostType = 'post';
			}
		} else {
			$selectedPostType = 'product';
		}

		if ($this->sliderType === 'post') {
			$postTypes = get_post_types(array(), 'objects');
			if (isset($postTypes['attachment'])) unset($postTypes['attachment']);
			if (isset($postTypes['revision'])) unset($postTypes['revision']);
			if (isset($postTypes['nav_menu_item'])) unset($postTypes['nav_menu_item']);

			// Reset default post_type
			if (count($postTypes) && !isset($postTypes['post'])) {
				$defaultPostType = reset(array_keys($postTypes));
			}

		} else {
			$postTypes = array('product' => get_post_type_object('product'));
		}

		if (count($postTypes)) {
			$categories = $tags = array();

			foreach ($postTypes as $postTypeName => $postType) {
				if (is_null($postType)) continue;

				$postTypeHierarchicalTaxs = $this->getTaxonomyName($postTypeName);
				$categories = $this->getTaxTerms($postTypeHierarchicalTaxs, $postTypeName, 'categories');
				$tags = $this->getTaxTerms($postTypeHierarchicalTaxs, $postTypeName, 'tags');
				// Get post-formats only once (because they are shared
				if (!count($_format)) $postFormats = $this->getTaxTerms($postTypeHierarchicalTaxs, $postTypeName, 'format');

				if (post_type_supports($postTypeName, 'post-formats')) {
					$postFormatsDependency[] = $postTypeName;
				}
				if (count(array_intersect(array('post_tag', 'product_tag'), array_keys($postTypeHierarchicalTaxs))) > 0) {
					$tagsDependency[] = $postTypeName;
				}
				if (count(array_intersect(array('category', 'product_cat'), array_keys($postTypeHierarchicalTaxs))) > 0) {
					$catDependency[] = $postTypeName;
				}

				if (count($categories) || count($tags)) {
					$postTypesArr[$postTypeName] = array(
						'label' => $postType->labels->singular_name,
						'attrs' => array(
							'data-categories' => $categories,
							'data-tags' => $tags,
//							'data-formats' => $postFormats
						)
					);
				} else {
					$postTypesArr[$postTypeName] = array(
						'label' => $postType->labels->singular_name,
						'attrs' => array()
					);
				}

//				if ($postTypeHierarchicalTaxs) $allPostTypesArr[] = $postTypeName;

				if (
					($this->sliderType === 'post' && $postTypeName === $selectedPostType) ||
					($this->sliderType === 'woocommerce' && $postTypeName === 'product')
				) {
					if (!count($_categories)) {
						foreach ($categories as $cat) {
							$_categories[$cat['key']] = $cat['value'];
						}
					}
					if (!count($_tags)) {
						foreach ($tags as $tag) {
							$_tags[$tag['key']] = $tag['value'];
						}
					}
				}
				if (!count($_format)) {
					foreach ($postFormats as $format) {
						$_format[$format['key']] = $format['value'];
					}
				}

			}
		}

	}

}

$sliderSettings = array(
    'main' => array(
        'title' => __('Slider Settings', 'motopress-slider'),
        'icon' => null,
        'description' => '',
        'options' => array(
            'slider_type' => array(
                'type' => 'select',
                'default' => 'custom',
                'list' => array(
                    'custom' => 'custom',
                    'post' => 'post',
                    'woocommerce' => 'woocommerce'
                ),
                'hidden' => true,
            ),
            'title' => array(
                'type' => 'text',
                'label' => __('Slider title *:', 'motopress-slider'),
                'description' => __('The title of the slider. Example: Slider1', 'motopress-slider'),
                'default' => __('New Slider', 'motopress-slider'),
                'disabled' => false,
                'required' => true,
            ),
            'alias' => array(
                'type' => 'alias',
                'label' => __('Slider alias *:', 'motopress-slider'),
                'alias' => 'shortcode',
                'description' => __('The alias that will be used in shortcode for embedding the slider. Alias must be unique. Example: slider1', 'motopress-slider'),
                'default' => '',
                'disabled' => false,
                'required' => true,
            ),
            'shortcode' => array(
                'type' => 'shortcode',
                'label' => __('Slider shortcode:', 'motopress-slider'),
                'description' => 'Copy this shortocode and paste to your page.',
                'default' => '',
                'readonly' => true,
//                'disabled' => false,
            ),
            'full_width' => array(
                'type' => 'checkbox',
                'label' => '',
                'label2' => __('Force Full Width', 'motopress-slider'),
                'description' => __('Enable this option to make this slider full-width / wide-screen', 'motopress-slider'),
                'default' => false
            ),
			'full_height' => array(
				'type' => 'checkbox',
				'label' => '',
				'label2' => __('Force Full Height', 'motopress-slider'),
				'description' => __('Enable this option to make this slider full-height', 'motopress-slider'),
				'default' => false,
			),

			'full_height_offset' => array(
				'type' => 'number',
				'label' => __('Full height increment:', 'motopress-slider'),
				'description' => __('Slider height will be increased or decreased to this value', 'motopress-slider'),
				'default' => '',
				'dependency' => array(
					'parameter' => 'full_height',
					'value' => true,
				)
			),

			'full_height_units' => array(
				'type' => 'select',
				'label' => __('Increment units:', 'motopress-slider'),
				'default' => 'px',
				'list' => array(
					'px' => __('Pixels (px)', 'motopress-slider'),
					'%' => __('Percents (%)', 'motopress-slider'),
				),
				'dependency' => array(
					'parameter' => 'full_height',
					'value' => true,
				)
			),

			'full_height_container' => array(
				'type' => 'text',
				'label' => __('Offset by container:', 'motopress-slider'),
				'description' => __('The height will be decreased with the height of these elements. Enter CSS Selector.', 'motopress-slider'),
				'default' => '',
				'dependency' => array(
					'parameter' => 'full_height',
					'value' => true,
				)
			),

			'full_size_grid' => array(
				'type' => 'checkbox',
				'label' => '',
				'description' => __('Enable this option to make grid stretch to parent container. Even if you select this option you still need to set Grid width and height to define slider size. If you check Force Full Width and/or Force Full Height options, the slider will be stretched to screen edges.', 'motopress-slider'),
				'label2' => __('Force Full Size Grid', 'motopress-slider'),
				'default' => false,
//				'dependency' => array(
//					'parameter' => 'full_height',
//					'value' => true,
//				)
			),

			'width' => array(
                'type' => 'number',
                'label' => __('Layers Grid Size', 'motopress-slider'),
                'label2' => __('Width:', 'motopress-slider'),
                'description' => __('Initial width of the layers', 'motopress-slider'),
//                'pattern' => '/^(0|[1-9][0-9]*)$/',
                'default' => 960,
                'min' => 0,
//                'disabled' => false
            ),
            'height' => array(
                'type' => 'number',
                'label' => '',
                'label2' => __('Height:', 'motopress-slider'),
                'description' => __('Initial height of the layers', 'motopress-slider'),
                'default' => 350,
                'min' => 0,
//                'disabled' => false
            ),
            /*'min_height' => array(
                'type' => 'number',
                'label2' => __('Min. Height:'),
                'default' => 500
            ),*/
            'enable_timer' => array(
                'type' => 'checkbox',
                'label' => '',
                'label2' => __('Enable Slideshow', 'motopress-slider'),
                'default' => true,
//                'disabled' => false
            ),
            'slider_delay' => array(
                'type' => 'text',
                'label' => __('Slideshow Delay:', 'motopress-slider'),
                'description' => __('The time one slide stays on the screen in milliseconds', 'motopress-slider'),
                'default' => 7000
            ),
            'slider_animation' => array(
                'type' => 'select',
                'label' => __('Slideshow Animation:', 'motopress-slider'),
                'default' => 'msSlide',
                   'list' => array(
                       'msSlide' => __('Slide', 'motopress-slider'),
                       'msSlideFade' => __('Fade', 'motopress-slider'),
                       'msSlideUpDown' => __('Slide Up', 'motopress-slider'),
                    ),
                    //'description' => __('Select slideshow animation', 'motopress-slider'),
            ),
            'slider_duration' => array(
                'type' => 'text',
                'label' => __('Slideshow Duration:', 'motopress-slider'),
                'description' => __('Animation duration in milliseconds', 'motopress-slider'),
                'default' => 2000
            ),
            'slider_easing' => array(
                'type' => 'select',
                'label' => __('Slideshow Easing:', 'motopress-slider'),
                'default' => 'easeOutCirc',
                   'list' => array(
                       'linear' => __('linear', 'motopress-slider'),
                       'ease' => __('ease', 'motopress-slider'),
                       'easeIn' => __('easeIn', 'motopress-slider'),
                       'easeOut' => __('easeOut', 'motopress-slider'),
                       'easeInOut' => __('easeInOut', 'motopress-slider'),
                       'easeInQuad' => __('easeInQuad', 'motopress-slider'),
                       'easeInCubic' => __('easeInCubic', 'motopress-slider'),
                       'easeInQuart' => __('easeInQuart', 'motopress-slider'),
                       'easeInQuint' => __('easeInQuint', 'motopress-slider'),
                       'easeInSine' => __('easeInSine', 'motopress-slider'),
                       'easeInExpo' => __('easeInExpo', 'motopress-slider'),
                       'easeInCirc' => __('easeInCirc', 'motopress-slider'),
                       'easeInBack' => __('easeInBack', 'motopress-slider'),
                       'easeOutQuad' => __('easeOutQuad', 'motopress-slider'),
                       'easeOutCubic' => __('easeOutCubic', 'motopress-slider'),
                       'easeOutQuart' => __('easeOutQuart', 'motopress-slider'),
                       'easeOutQuint' => __('easeOutQuint', 'motopress-slider'),
                       'easeOutSine' => __('easeOutSine', 'motopress-slider'),
                       'easeOutExpo' => __('easeOutExpo', 'motopress-slider'),
                       'easeOutCirc' => __('easeOutCirc', 'motopress-slider'),
                       'easeOutBack' => __('easeOutBack', 'motopress-slider'),
                       'easeInOutQuad' => __('easeInOutQuad', 'motopress-slider'),
                       'easeInOutCubic' => __('easeInOutCubic', 'motopress-slider'),
                       'easeInOutQuart' => __('easeInOutQuart', 'motopress-slider'),
                       'easeInOutQuint' => __('easeInOutQuint', 'motopress-slider'),
                       'easeInOutSine' => __('easeInOutSine', 'motopress-slider'),
                       'easeInOutExpo' => __('easeInOutExpo', 'motopress-slider'),
                       'easeInOutCirc' => __('easeInOutCirc', 'motopress-slider'),
                       'easeInOutBack' => __('easeInOutBack', 'motopress-slider'),
                   ),
                'description' => __('<a href="https://jqueryui.com/easing/" target="_blank">Easing examples</a>', 'motopress-slider'),
//                'dependency' => array(
//                    'parameter' => 'slider_animation',
//                    'value' => 'msSlide'
//                ),
            ),
//            'post_slider' => array(
//                'type' => 'checkbox',
//                'label' => '',
//                'label2' => __('Post content', 'motopress-slider'),
//                'description' => __('Enable post slider', 'motopress-slider'),
//                'default' => false
//            ),

//            'slider_layout' => array(
//                'type' => 'select',
//                'label' => __('Slider Layout', 'motopress-slider'),
//                'default' => 'auto',
//                'list' => array(
//                    'auto' => __('Auto', 'motopress-slider')
//                )
//            ),
//            'description' => array(
//                'type' => 'textarea',
//                'label' => __('Description :', 'motopress-slider'),
//                'description' => __('Write some description', 'motopress-slider'),
//                'default' => 'Default description',
////                'disabled' => false,
//            ),
//            'test' => array(
//                'type' => 'select',
//                'label' => __('Test dependency', 'motopress-slider'),
//                'default' => 'off',
//                'list' => array(
//                    'on' => 'On',
//                    'off' => 'Off'
//                ),
//            ),
//            'test_dependency' => array(
//                'type' => 'text',
//                'label' => __('Test dependency input', 'motopress-slider'),
//                'default' => 'visible',
//                'dependency' => array(
//                    'parameter' => 'test',
//                    'value' => 'on'
//                ),
//            ),
//            'radio_group' => array(
//                'type' => 'radio_group',
//                'label' => __('Test radiogroup', 'motopress-slider'),
//                'default' => 'one',
//                'list' => array(
//                    'one' => 'One',
//                    'two' => 'Two',
//                    'three' => 'Three',
//                )
//            ),
            'start_slide' => array(
                'type' => 'number',
                'label' => __('Start with slide:', 'motopress-slider'),
                'description' => __('Slide index in the list of slides', 'motopress-slider'),
                'default' => 1,
				'min' => 1
            ),
        )
    ),

    'controls' => array(
        'title' => __('Controls', 'motopress-slider'),
        'icon' => null,
        'description' => '',
        'options' => array(
            'arrows_show' => array(
                'type' => 'checkbox',
                'label2' => __('Show arrows', 'motopress-slider'),
                'default' => true
            ),
            'thumbnails_show' => array(
                'type' => 'checkbox',
                'label2' => __('Show bullets', 'motopress-slider'),
                'default' => true
            ),
            'slideshow_timer_show' => array(
                'type' => 'checkbox',
                'label2' => __('Show slideshow timer', 'motopress-slider'),
                'default' => true
            ),
            'slideshow_ppb_show' => array(
                'type' => 'checkbox',
                'label2' => __('Show slideshow play/pause button', 'motopress-slider'),
                'default' => true
            ),
            'controls_hide_on_leave' => array(
                'type' => 'checkbox',
                'label2' => __('Hide controls when mouse leaves slider', 'motopress-slider'),
                'default' => false
            ),
            'hover_timer' => array(
                'type' => 'checkbox',
                'label2' => __('Pause on Hover', 'motopress-slider'),
                'description' => __('Pause slideshow when hover the slider', 'motopress-slider'),
                'default' => false
            ),
            'timer_reverse' => array(
                'type' => 'checkbox',
                'label2' => __('Reverse order of the slides', 'motopress-slider'),
                'description' => __('Animate slides in the reverse order', 'motopress-slider'),
                'default' => false
            ),
            'counter' => array(
                'type' => 'checkbox',
                'label2' => __('Show counter', 'motopress-slider'),
                'description' => __('Displays the number of slides', 'motopress-slider'),
                'default' => false
            ),
            'swipe' => array(
                'type' => 'checkbox',
                'label2' => __('Enable swipe', 'motopress-slider'),
                'description' => __('Turn on swipe on desktop', 'motopress-slider'),
                'default' => true
            ),
			'edit_slider' => array(
				'type' => 'checkbox',
				'label2' => __('Show edit button', 'motopress-slider'),
				'description' => __('Display an icon for quick reference to slider settings', 'motopress-slider'),
				'default' => true,
			),
        )
    ),

    'appearance' => array(
        'title' => __('Appearance', 'motopress-slider'),
        'icon' => null,
        'description' => '',
        'options' => array(
            'visible_from' => array(
                'type' => 'number',
                'label' => __('Visible', 'motopress-slider'),
                'label2' => __('from', 'motopress-slider'),
                'unit' => 'px',
                'default' => '',
                'min' => 0,
            ),
            'visible_till' => array(
                'type' => 'number',
                'label' => '',
                'label2' => __('till', 'motopress-slider'),
                'unit' => 'px',
                'default' => '',
                'min' => 0,
            ),
            'presets' => array(
                'type' => 'action_group',
                'label' => '',
                'label2' => __('presets:', 'motopress-slider'),
                'default' => '',
                'list' => array(
                    'phone' => __('Phone', 'motopress-slider'),
                    'tablet' => __('Tablet', 'motopress-slider'),
                    'desktop' => __('Desktop', 'motopress-slider')
                ),
                'actions' => array(
                    'phone' => array(
                        'visible_from' => '',
                        'visible_till' => 767
                    ),
                    'tablet' => array(
                        'visible_from' => 768,
                        'visible_till' => 991
                    ),
                    'desktop' => array(
                        'visible_from' => 992,
                        'visible_till' => ''
                    )
                )
            ),
            'delay_init' => array(
                'type' => 'text',
                'label' => __('Initialization delay:', 'motopress-slider'),
                //'description' => __('Type slider init delay', 'motopress-slider'),
                'default' => 0
            ),

            'scroll_init' => array(
                'type' => 'checkbox',
                'label' => '',
                'label2' => __('Initialize slider on scroll', 'motopress-slider'),
                //'description' => __('Enable this option to init slider on scroll', 'motopress-slider'),
                'default' => false
            ),
            'custom_class' => array(
                'type' => 'text',
                'label' => __('Slider custom class name', 'motopress-slider'),
                'default' => ''
            ),
            'custom_styles' => array(
                'type' => 'codemirror',
                'mode' => 'css',
                'label2' => __('Slider custom styles', 'motopress-slider'),
                'default' => ''
            ),
        )
    ),

);

if (in_array($this->sliderType, array('post', 'woocommerce'))) {

	// Taxonomy dependencies
	$taxDepsParam = $postFormatsTaxDepsParam = 'post_type';
	if ($this->sliderType === 'woocommerce') {
		$taxDepsParam = 'slider_type';
		$catDependency = $tagsDependency = 'woocommerce';
		
		if (in_array('product', $postFormatsDependency)) {
			$postFormatsTaxDepsParam = 'slider_type';
			$postFormatsDependency = 'woocommerce';
		}
	};

	$postSliderLabels = array();
	switch ($this->sliderType) {
		case 'post':
			$postSliderLabels = array(
				'tab_label' => __('Content', 'motopress-slider'),
                'exclude_label' => __('Exclude posts:', 'motopress-slider'),
                'exclude_description' => __('post id\'s separated by comma', 'motopress-slider'),
                'include_label' => __('Include posts:', 'motopress-slider'),
                'include_description' => __('post id\'s separated by comma', 'motopress-slider'),
                'count_label' => __('Number of posts to display: ', 'motopress-slider'),
                'link_label' => __('Link slides to post\'s page: ', 'motopress-slider'),
			);
			break;
		case 'woocommerce':
			$postSliderLabels = array(
				'tab_label' => __('Content', 'motopress-slider'),
                'exclude_label' => __('Exclude products:', 'motopress-slider'),
                'exclude_description' => __('product id\'s separated by comma', 'motopress-slider'),
                'include_label' => __('Include products:', 'motopress-slider'),
                'include_description' => __('product id\'s separated by comma', 'motopress-slider'),
                'count_label' => __('Number of products to display: ', 'motopress-slider'),
                'link_label' => __('Link slides to product\'s page: ', 'motopress-slider'),
			);
			break;
	}


	$sliderSettings['post_settings'] = array(
		'title' => $postSliderLabels['tab_label'],
		'icon' => null,
		'description' => '',
		'options' => array(
			'post_type' => array(
				'type' => 'select',
				'label' => __('Select Post type:', 'motopress-slider'),
				'default' => $defaultPostType,
				'list' => $postTypesArr,
				'listAttrSettings' => array(
		            'data-categories' => array(
			            'type' => 'json',
		            ),
                    'data-tags' => array(
                        'type' => 'json',
                    )
	            ),
				'dependency' => array(
					'parameter' => 'slider_type',
					'value' => 'post',
				)
			),

			'post_categories' => array(
				'type' => 'select',
				'label' => __('Categories:', 'motopress-slider'),
				'default' => 0,
				'multiple' => true,
				'list' => $_categories,
				'helpers' => array('post_type'),
				'dynamicList' => array(
					'parameter' => 'post_type',
					'attr' => 'data-categories',
				),
                'dependency' => array(
					'parameter' => $taxDepsParam,
					'value' => $catDependency,
				)
			),

			'post_tags' => array(
				'type' => 'select',
				'label' => __('Tags:', 'motopress-slider'),
				'default' => 0,
				'multiple' => true,
				'list' => $_tags,
                'helpers' => array('post_type'),
                'dynamicList' => array(
                    'parameter' => 'post_type',
                    'attr' => 'data-tags',
                ),
                'dependency' => array(
                    'parameter' => $taxDepsParam,
                    'value' => $tagsDependency,
                )
			),

			'post_format' => array(
				'type' => 'select',
				'label' => __('Post Format:', 'motopress-slider'),
				'default' => 0,
				'multiple' => true,
				'list' => $_format,
				'dependency' => array(
					'parameter' => $postFormatsTaxDepsParam,
					'value' => $postFormatsDependency,
				)
			),

			'post_exclude_ids' => array(
				'type' => 'text',
				'label' => $postSliderLabels['exclude_label'],
				'description' => $postSliderLabels['exclude_description'],
				'default' => '',
				'dependency' => array(
					'parameter' => 'slider_type',
					'value' => array('post', 'woocommerce'),
				)
			),
			'post_include_ids' => array(
				'type' => 'text',
				'label' => $postSliderLabels['include_label'],
				'description' => $postSliderLabels['include_description'],
				'default' => '',
				'dependency' => array(
					'parameter' => 'slider_type',
					'value' => array('post', 'woocommerce'),
				)
			),
			'post_count' => array(
				'type' => 'number',
				'label' => $postSliderLabels['count_label'],
				'default' => 10,
				'min' => -1,
				'dependency' => array(
					'parameter' => 'slider_type',
					'value' => array('post', 'woocommerce'),
				)
			),
			'post_excerpt_length' => array(
				'type' => 'number',
				'label' => __('Excerpt length:', 'motopress-slider'),
				'description' => __('character(s)', 'motopress-slider'),
				'default' => 200,
				'min' => 0,
				'dependency' => array(
					'parameter' => 'slider_type',
					'value' => array('post', 'woocommerce'),
				)
			),
			'post_offset' => array(
				'type' => 'number',
				'label' => __('Number of first results to skip (offset):', 'motopress-slider'),
				'default' => '',
				'min' => 0,
				'dependency' => array(
					'parameter' => 'slider_type',
					'value' => array('post', 'woocommerce'),
				)
			),
			'post_link_slide' => array(
				'type' => 'checkbox',
				'label' => $postSliderLabels['link_label'],
				'default' => false,
				'dependency' => array(
					'parameter' => 'slider_type',
					'value' => array('post', 'woocommerce'),
				)
			),
			'post_link_target' => array(
				'type' => 'checkbox',
				'label' => __('Open in new window:', 'motopress-slider'),
				'default' => false,
				'dependency' => array(
					'parameter' => 'post_link_slide',
					'value' => true,
				)
			),
			'post_order_by' => array(
				'type' => 'select',
				'label' => __('Order By:', 'motopress-slider'),
				'default' => 'date',
				'list' => array(
					'date' => 'Date',
					'menu_order' => 'Menu Order',
					'title' => 'Title',
					'id' => 'Id',
					'random' => 'Random',
					'comments' => 'Comments',
					'date_modified' => 'Date Modified',
					'none' => 'None'
				),
				'dependency' => array(
					'parameter' => 'slider_type',
					'value' => array('post', 'woocommerce'),
				)
			),
			'post_order_direction' => array(
				'type' => 'select',
				'label' => __('Order direction:', 'motopress-slider'),
				'default' => 'DESC',
				'list' => array(
					'DESC' => 'Descending (largest to smallest)',
					'ASC' => 'Ascending (smallest to largest)',
				),
				'dependency' => array(
					'parameter' => 'slider_type',
					'value' => array('post', 'woocommerce'),
				)
			),
		),
	);

	if ($this->sliderType === 'woocommerce') {
		$sliderSettings['post_settings']['options'] = array_merge($sliderSettings['post_settings']['options'], array(
			'wc_only_instock' => array(
				'type' => 'checkbox',
				'label' => __('Only display in-stock products. ', 'motopress-slider'),
				'default' => false,
				'dependency' => array(
					'parameter' => 'slider_type',
					'value' => 'woocommerce',
				)
			),
			'wc_only_featured' => array(
				'type' => 'checkbox',
				'label' => __('Only display featured products. ', 'motopress-slider'),
				'default' => false,
				'dependency' => array(
					'parameter' => 'slider_type',
					'value' => 'woocommerce',
				)
			),
			'wc_only_onsale' => array(
				'type' => 'checkbox',
				'label' => __('Only display on sale products. ', 'motopress-slider'),
				'default' => false,
				'dependency' => array(
					'parameter' => 'slider_type',
					'value' => 'woocommerce',
				)
			)
		));
	}
}


if ($this->sliderType === 'post') {
	$sliderSettings['main']['options']['title']['default'] = __('New Posts Slider', 'motopress-slider');
} else if ($this->sliderType === 'woocommerce') {
	$sliderSettings['main']['options']['title']['default'] = __('New WooCommerce Slider', 'motopress-slider');
}

return $sliderSettings;