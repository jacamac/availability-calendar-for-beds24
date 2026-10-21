<?php
/**
 * Availability Calendar for Beds24 — Elementor Widget
 *
 * Copyright (C) 2026 Jacques Leisy
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Availability_Calendar_For_Beds24
 */

namespace BAC;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor widget that renders a Beds24 availability calendar.
 */
class Avail_Calendar_Widget extends \Elementor\Widget_Base {

	// ─── Identity ────────────────────────────────────────

	/**
	 * Returns the widget's unique name identifier.
	 *
	 * @return string Widget name.
	 */
	public function get_name(): string {
		return 'avail_calendar';
	}

	/**
	 * Returns the widget's display title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return esc_html__( 'Availability Calendar', 'availability-calendar-for-beds24' );
	}

	/**
	 * Returns the widget's icon class.
	 *
	 * @return string Icon class name.
	 */
	public function get_icon(): string {
		return 'eicon-calendar';
	}

	/**
	 * Returns the widget's Elementor editor categories.
	 *
	 * @return array<int, string> Category slugs.
	 */
	public function get_categories(): array {
		return array( 'general' );
	}

	/**
	 * Returns search keywords for this widget in the Elementor panel.
	 *
	 * @return array<int, string> Keyword strings.
	 */
	public function get_keywords(): array {
		return array( 'beds24', 'availability', 'calendar', 'booking' );
	}

	// ─── Controls (editor panel) ──────────────────────────

	/**
	 * Register Elementor editor controls for this widget.
	 */
	protected function register_controls(): void {

		/* ── Section: Beds24 Connection ── */
		$this->start_controls_section(
			'section_connection',
			array(
				'label' => esc_html__( 'Beds24 Connection', 'availability-calendar-for-beds24' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'id_notice',
			array(
				'type'        => \Elementor\Controls_Manager::NOTICE,
				'notice_type' => 'info',
				'content'     => esc_html__( 'Set either a Room ID or a Property ID — not both.', 'availability-calendar-for-beds24' ),
			)
		);

		$this->add_control(
			'roomid',
			array(
				'label'              => esc_html__( 'Room ID', 'availability-calendar-for-beds24' ),
				'type'               => \Elementor\Controls_Manager::TEXT,
				'input_type'         => 'number',
				'placeholder'        => esc_html__( 'e.g. 12345', 'availability-calendar-for-beds24' ),
				'description'        => esc_html__( 'Beds24 room ID for a single room calendar.', 'availability-calendar-for-beds24' ),
				'frontend_available' => true,
			)
		);

		$this->add_control(
			'propid',
			array(
				'label'              => esc_html__( 'Property ID', 'availability-calendar-for-beds24' ),
				'type'               => \Elementor\Controls_Manager::TEXT,
				'input_type'         => 'number',
				'placeholder'        => esc_html__( 'e.g. 67890', 'availability-calendar-for-beds24' ),
				'description'        => esc_html__( 'Beds24 property ID for a whole-property calendar.', 'availability-calendar-for-beds24' ),
				'frontend_available' => true,
			)
		);

		$this->add_control(
			'beds24_booking',
			array(
				'label'        => esc_html__( 'Book on Beds24', 'availability-calendar-for-beds24' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Off (default): a day opens this site\'s /booking/ page with the date and room. On: Beds24\'s booking page.', 'availability-calendar-for-beds24' ),
			)
		);

		$this->end_controls_section();

		/* ── Section: Display ── */
		$this->start_controls_section(
			'section_display',
			array(
				'label' => esc_html__( 'Display', 'availability-calendar-for-beds24' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'nummonths',
			array(
				'label'              => esc_html__( 'Months to Display', 'availability-calendar-for-beds24' ),
				'type'               => \Elementor\Controls_Manager::SLIDER,
				'size_units'         => array(),
				'range'              => array(
					'px' => array(
						'min'  => 1,
						'max'  => 12,
						'step' => 1,
					),
				),
				'default'            => array(
					'unit' => 'px',
					'size' => BAC_DEFAULT_MONTHS_DESKTOP,
				),
				'tablet_default'     => array(
					'unit' => 'px',
					'size' => BAC_DEFAULT_MONTHS_TABLET,
				),
				'mobile_default'     => array(
					'unit' => 'px',
					'size' => BAC_DEFAULT_MONTHS_MOBILE,
				),
				'frontend_available' => true,
			)
		);

		$this->add_control(
			'startmonth',
			array(
				'label'              => esc_html__( 'Start Month', 'availability-calendar-for-beds24' ),
				'type'               => \Elementor\Controls_Manager::SELECT,
				'default'            => '0',
				'frontend_available' => true,
				'options'            => array(
					'0'  => esc_html__( 'Current month', 'availability-calendar-for-beds24' ),
					'1'  => esc_html__( 'January', 'availability-calendar-for-beds24' ),
					'2'  => esc_html__( 'February', 'availability-calendar-for-beds24' ),
					'3'  => esc_html__( 'March', 'availability-calendar-for-beds24' ),
					'4'  => esc_html__( 'April', 'availability-calendar-for-beds24' ),
					'5'  => esc_html__( 'May', 'availability-calendar-for-beds24' ),
					'6'  => esc_html__( 'June', 'availability-calendar-for-beds24' ),
					'7'  => esc_html__( 'July', 'availability-calendar-for-beds24' ),
					'8'  => esc_html__( 'August', 'availability-calendar-for-beds24' ),
					'9'  => esc_html__( 'September', 'availability-calendar-for-beds24' ),
					'10' => esc_html__( 'October', 'availability-calendar-for-beds24' ),
					'11' => esc_html__( 'November', 'availability-calendar-for-beds24' ),
					'12' => esc_html__( 'December', 'availability-calendar-for-beds24' ),
				),
			)
		);

		$this->add_control(
			'startyear',
			array(
				'label'              => esc_html__( 'Start Year', 'availability-calendar-for-beds24' ),
				'type'               => \Elementor\Controls_Manager::TEXT,
				'input_type'         => 'number',
				'placeholder'        => esc_html__( 'Leave empty for current year', 'availability-calendar-for-beds24' ),
				'frontend_available' => true,
			)
		);

		$this->end_controls_section();

		/* ── Section: Language ── */
		$this->start_controls_section(
			'section_language',
			array(
				'label' => esc_html__( 'Language', 'availability-calendar-for-beds24' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'lang_notice',
			array(
				'type'        => \Elementor\Controls_Manager::NOTICE,
				'notice_type' => 'info',
				'content'     => esc_html__( 'Leave blank to use the language detected from TranslatePress (or WordPress locale if TranslatePress is not active).', 'availability-calendar-for-beds24' ),
			)
		);

		$this->add_control(
			'lang',
			array(
				'label'              => esc_html__( 'Language Override', 'availability-calendar-for-beds24' ),
				'type'               => \Elementor\Controls_Manager::TEXT,
				'placeholder'        => esc_html__( 'e.g. fr, de, en, it', 'availability-calendar-for-beds24' ),
				'description'        => esc_html__( 'Any BCP-47 language tag. Leave blank for automatic detection.', 'availability-calendar-for-beds24' ),
				'frontend_available' => true,
			)
		);

		$this->end_controls_section();

		/*
		══════════════════════════════════════════════════════
			STYLE TAB
			══════════════════════════════════════════════════════
		*/

		/* ── Section: Display Scheme ── */
		$this->start_controls_section(
			'section_scheme',
			array(
				'label' => esc_html__( 'Display Scheme', 'availability-calendar-for-beds24' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'scheme',
			array(
				'label'   => esc_html__( 'Scheme', 'availability-calendar-for-beds24' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'solid',
				'options' => array(
					'solid'   => esc_html__( 'Solid', 'availability-calendar-for-beds24' ),
					'minimal' => esc_html__( 'Minimal', 'availability-calendar-for-beds24' ),
					'outline' => esc_html__( 'Outline', 'availability-calendar-for-beds24' ),
					'soft'    => esc_html__( 'Soft', 'availability-calendar-for-beds24' ),
					'dot'     => esc_html__( 'Dot', 'availability-calendar-for-beds24' ),
					'custom'  => esc_html__( 'Custom', 'availability-calendar-for-beds24' ),
				),
			)
		);

		$custom = array( 'scheme' => 'custom' );

		$this->add_control(
			'scheme_shape',
			array(
				'label'     => esc_html__( 'Day Shape', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'rounded',
				'condition' => $custom,
				'options'   => array(
					'rounded' => esc_html__( 'Rounded', 'availability-calendar-for-beds24' ),
					'square'  => esc_html__( 'Square', 'availability-calendar-for-beds24' ),
					'circle'  => esc_html__( 'Circle', 'availability-calendar-for-beds24' ),
				),
			)
		);

		$this->add_control(
			'scheme_avail',
			array(
				'label'     => esc_html__( 'Available Style', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'fill',
				'condition' => $custom,
				'options'   => array(
					'fill'    => esc_html__( 'Fill', 'availability-calendar-for-beds24' ),
					'outline' => esc_html__( 'Outline', 'availability-calendar-for-beds24' ),
					'ghost'   => esc_html__( 'Ghost', 'availability-calendar-for-beds24' ),
					'dot'     => esc_html__( 'Dot', 'availability-calendar-for-beds24' ),
					'plain'   => esc_html__( 'Plain', 'availability-calendar-for-beds24' ),
				),
			)
		);

		$this->add_control(
			'scheme_unavail',
			array(
				'label'     => esc_html__( 'Unavailable Style', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'fill',
				'condition' => $custom,
				'options'   => array(
					'fill'          => esc_html__( 'Fill', 'availability-calendar-for-beds24' ),
					'outline'       => esc_html__( 'Outline', 'availability-calendar-for-beds24' ),
					'ghost'         => esc_html__( 'Ghost', 'availability-calendar-for-beds24' ),
					'dot'           => esc_html__( 'Dot', 'availability-calendar-for-beds24' ),
					'strikethrough' => esc_html__( 'Strikethrough', 'availability-calendar-for-beds24' ),
					'dim'           => esc_html__( 'Dim', 'availability-calendar-for-beds24' ),
					'hidden'        => esc_html__( 'Hidden', 'availability-calendar-for-beds24' ),
				),
			)
		);

		$this->add_control(
			'scheme_past',
			array(
				'label'     => esc_html__( 'Past Days', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'muted',
				'condition' => $custom,
				'options'   => array(
					'muted'       => esc_html__( 'Muted', 'availability-calendar-for-beds24' ),
					'unavailable' => esc_html__( 'Same as unavailable', 'availability-calendar-for-beds24' ),
					'hidden'      => esc_html__( 'Hidden', 'availability-calendar-for-beds24' ),
				),
			)
		);

		$this->add_control(
			'scheme_hover',
			array(
				'label'     => esc_html__( 'Hover Effect', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'brighten',
				'condition' => $custom,
				'options'   => array(
					'brighten' => esc_html__( 'Brighten', 'availability-calendar-for-beds24' ),
					'fill'     => esc_html__( 'Fill', 'availability-calendar-for-beds24' ),
					'outline'  => esc_html__( 'Outline', 'availability-calendar-for-beds24' ),
					'scale'    => esc_html__( 'Scale', 'availability-calendar-for-beds24' ),
					'lift'     => esc_html__( 'Lift', 'availability-calendar-for-beds24' ),
				),
			)
		);

		$this->add_control(
			'scheme_today',
			array(
				'label'     => esc_html__( 'Today Indicator', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'ring-rect',
				'condition' => $custom,
				'options'   => array(
					'ring-rect'   => esc_html__( 'Ring (rounded)', 'availability-calendar-for-beds24' ),
					'ring-circle' => esc_html__( 'Ring (circle)', 'availability-calendar-for-beds24' ),
					'bold'        => esc_html__( 'Bold text', 'availability-calendar-for-beds24' ),
					'none'        => esc_html__( 'None', 'availability-calendar-for-beds24' ),
				),
			)
		);

		$this->add_control(
			'scheme_nav',
			array(
				'label'     => esc_html__( 'Nav Arrows', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'split',
				'condition' => $custom,
				'options'   => array(
					'split'     => esc_html__( 'Split (flanking months)', 'availability-calendar-for-beds24' ),
					'top-left'  => esc_html__( 'Top left', 'availability-calendar-for-beds24' ),
					'top-right' => esc_html__( 'Top right', 'availability-calendar-for-beds24' ),
				),
			)
		);

		$this->end_controls_section();

		/* ── Section: Colors ── */
		$this->start_controls_section(
			'section_style_colors',
			array(
				'label' => esc_html__( 'Colors', 'availability-calendar-for-beds24' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'heading_avail_colors',
			array(
				'label' => esc_html__( 'Availability', 'availability-calendar-for-beds24' ),
				'type'  => \Elementor\Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'color_available',
			array(
				'label'     => esc_html__( 'Available', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#34a853',
				'selectors' => array(
					'{{WRAPPER}} .bac-wrapper' => '--avail-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_unavailable',
			array(
				'label'     => esc_html__( 'Unavailable', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ea4335',
				'selectors' => array(
					'{{WRAPPER}} .bac-wrapper' => '--unavail-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'heading_calendar_colors',
			array(
				'label'     => esc_html__( 'Calendar', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'color_background',
			array(
				'label'     => esc_html__( 'Background', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .bac-wrapper' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_text',
			array(
				'label'     => esc_html__( 'Text', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => array(
					'{{WRAPPER}} .bac-wrapper'    => 'color: {{VALUE}};',
					'{{WRAPPER}} .bac-nav button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_past',
			array(
				'label'     => esc_html__( 'Past Days', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#cccccc',
				'selectors' => array(
					'{{WRAPPER}} .bac-day.bac-past' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_month_title',
			array(
				'label'     => esc_html__( 'Month Title', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => array(
					'{{WRAPPER}} .bac-month-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_day_headers',
			array(
				'label'     => esc_html__( 'Day Headers', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#999999',
				'selectors' => array(
					'{{WRAPPER}} .bac-dow-cell' => 'color: {{VALUE}};',
					'{{WRAPPER}} .bac-status'   => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_today',
			array(
				'label'     => esc_html__( 'Today Ring', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => array(
					'{{WRAPPER}} .bac-wrapper' => '--cal-today-ring: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		/* ── Section: Typography ── */
		$this->start_controls_section(
			'section_style_typography',
			array(
				'label' => esc_html__( 'Typography', 'availability-calendar-for-beds24' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_calendar',
				'label'    => esc_html__( 'Calendar Font', 'availability-calendar-for-beds24' ),
				'selector' => '{{WRAPPER}} .bac-wrapper',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_month_title',
				'label'    => esc_html__( 'Month Title', 'availability-calendar-for-beds24' ),
				'selector' => '{{WRAPPER}} .bac-month-title',
			)
		);

		$this->end_controls_section();

		/* ── Section: Tiles ── */
		$this->start_controls_section(
			'section_style_tiles',
			array(
				'label' => esc_html__( 'Tiles', 'availability-calendar-for-beds24' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'tile_size',
			array(
				'label'      => esc_html__( 'Tile Size', 'availability-calendar-for-beds24' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 24,
						'max'  => 60,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 36,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bac-day'     => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bac-dow-row' => 'grid-template-columns: repeat(7, {{SIZE}}{{UNIT}});',
					'{{WRAPPER}} .bac-grid'    => 'grid-template-columns: repeat(7, {{SIZE}}{{UNIT}});',
				),
			)
		);

		$this->add_control(
			'tile_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'availability-calendar-for-beds24' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 7,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bac-wrapper' => '--tile-r: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'tile_gap',
			array(
				'label'      => esc_html__( 'Tile Gap', 'availability-calendar-for-beds24' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 16,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 4,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bac-dow-row' => 'gap: {{SIZE}}{{UNIT}}; margin-bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bac-grid'    => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'month_gap',
			array(
				'label'      => esc_html__( 'Month Gap', 'availability-calendar-for-beds24' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 80,
						'step' => 4,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 28,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bac-strip' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	// ─── Dynamic content flag ────────────

	/**
	 * Tell Elementor to render this widget server-side (via AJAX) when roomid
	 * or propid is supplied through a dynamic tag (e.g. an ACF field).
	 *
	 * In that case content_template() cannot resolve the dynamic value; the PHP
	 * render() method can via get_settings_for_display(). Elementor replaces the
	 * preview DOM with the server-rendered HTML whenever a setting changes.
	 */
	public function is_dynamic_content(): bool {
		// Always render server-side in the editor so dynamic tags (ACF fields,
		// post meta, etc.) are resolved via get_settings_for_display() in PHP.
		// NOTE: must NOT call get_settings() here — Elementor calls this method
		// on widget types during editor script loading, before any instance is
		// bound to a post, so get_settings() returns null and causes a fatal error.
		return true;
	}

	// ─── Render (front-end + Elementor preview) ────────────

	/**
	 * Extract an integer size from a slider setting value.
	 * Elementor stores slider values as ['unit' => 'px', 'size' => n] but
	 * some contexts return a plain scalar, so handle both formats.
	 *
	 * @param mixed $val      Raw setting value (array or scalar).
	 * @param int   $fallback Value to return when the setting is absent or empty.
	 * @return int
	 */
	private function slider_size( $val, int $fallback ): int {
		if ( is_array( $val ) && isset( $val['size'] ) && '' !== $val['size'] ) {
			return (int) $val['size'];
		}
		if ( is_numeric( $val ) ) {
			return (int) $val;
		}
		return $fallback;
	}

	/**
	 * Render the widget output on the frontend.
	 */
	protected function render(): void {
		$s = $this->get_settings_for_display();

		// Build raw values from widget settings.
		$raw = array(
			'roomid'          => $s['roomid'] ?? '',
			'propid'          => $s['propid'] ?? '',
			'nummonths'       => $this->slider_size( $s['nummonths'] ?? array(), BAC_DEFAULT_MONTHS_DESKTOP ),
			'nummonthstablet' => $this->slider_size( $s['nummonths_tablet'] ?? array(), BAC_DEFAULT_MONTHS_TABLET ),
			'nummonthsmobile' => $this->slider_size( $s['nummonths_mobile'] ?? array(), BAC_DEFAULT_MONTHS_MOBILE ),
			'startmonth'      => $s['startmonth'] ?? '',
			'startyear'       => $s['startyear'] ?? '',
			'lang'            => $s['lang'] ?? '',
			'beds24_booking'  => $s['beds24_booking'] ?? '',
			'scheme'          => $s['scheme'] ?? 'solid',
			'scheme_shape'    => $s['scheme_shape'] ?? '',
			'scheme_avail'    => $s['scheme_avail'] ?? '',
			'scheme_unavail'  => $s['scheme_unavail'] ?? '',
			'scheme_past'     => $s['scheme_past'] ?? '',
			'scheme_hover'    => $s['scheme_hover'] ?? '',
			'scheme_today'    => $s['scheme_today'] ?? '',
			'scheme_nav'      => $s['scheme_nav'] ?? '',
		);

		$config = bac_sanitize_config( $raw );

		// In edit mode, attach the resolved config as a data attribute so the
		// Elementor preview JS handler can initialise AvailCalendar directly —
		// bac_footer_init() does not run during Elementor's AJAX widget render.
		$extra_attrs = array();
		if ( \Elementor\Plugin::$instance && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$preview_cfg = $config;
			unset( $preview_cfg['containerId'] );
			$extra_attrs['data-bac-cfg'] = (string) wp_json_encode( $preview_cfg );
		}

		$html = bac_register_instance( $config, $extra_attrs );

		echo wp_kses(
			$html,
			array(
				'div' => array(
					'id'           => true,
					'class'        => true,
					'aria-label'   => true,
					'data-bac-cfg' => true,
				),
			)
		);
	}

	// ─── Elementor editor live-preview template ────────────

	/**
	 * Underscore.js template used by Elementor for the editor canvas preview.
	 *
	 * All settings are serialised into a data-bac-cfg attribute so the JS handler
	 * can read them without depending on elementorFrontend.config (which only
	 * includes controls marked frontend_available and requires a page save to
	 * refresh). The template re-renders on every non-CSS setting change, so the
	 * attribute is always current.
	 */
	protected function content_template(): void {
		?>
		<#
		var containerId = 'bac-' + view.model.id;
		function _bacSz( v, fb ) {
			if ( ! v ) { return fb; }
			var n = ( 'object' === typeof v ) ? parseInt( v.size, 10 ) : parseInt( v, 10 );
			return ( isNaN( n ) || n < 1 ) ? fb : n;
		}
		// When is_dynamic_content() is true, Elementor renders server-side and
		// the data-bac-cfg attribute on the div carries the resolved value.
		// For static values, settings[key] contains the literal input.
		function _bacPlain( key ) {
			return String( settings[ key ] || '' ).trim();
		}
		var bacCfg = JSON.stringify( {
			roomid:          _bacPlain( 'roomid' ),
			propid:          _bacPlain( 'propid' ),
			numMonths:       _bacSz( settings.nummonths,        3 ),
			numMonthsTablet: _bacSz( settings.nummonths_tablet, 2 ),
			numMonthsMobile: _bacSz( settings.nummonths_mobile, 1 ),
			startMonth:      parseInt( settings.startmonth, 10 ) > 0 ? parseInt( settings.startmonth, 10 ) : null,
			startYear:       parseInt( settings.startyear,   10 ) > 0 ? parseInt( settings.startyear,   10 ) : null,
			lang:            _bacPlain( 'lang' ) || 'en',
			scheme:       _bacPlain( 'scheme' ) || 'solid',
			schemeShape:  _bacPlain( 'scheme_shape' ),
			schemeAvail:  _bacPlain( 'scheme_avail' ),
			schemeUnavail:_bacPlain( 'scheme_unavail' ),
			schemePast:   _bacPlain( 'scheme_past' ),
			schemeHover:  _bacPlain( 'scheme_hover' ),
			schemeToday:  _bacPlain( 'scheme_today' ),
			schemeNav:    _bacPlain( 'scheme_nav' ),
		} );
		#>
		<div id="{{ containerId }}" class="bac-wrapper" data-bac-cfg='{{{ bacCfg }}}'></div>
		<?php
	}
}
