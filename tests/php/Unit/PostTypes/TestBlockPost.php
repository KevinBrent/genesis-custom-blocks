<?php
/**
 * Tests for class BlockPost.
 *
 * @package Genesis\CustomBlocks
 */

use Genesis\CustomBlocks\PostTypes\BlockPost;
use Genesis\CustomBlocks\Blocks\Controls\User;

/**
 * Tests for class BlockPost.
 */
class TestBlockPost extends \WP_UnitTestCase {

	use TestingHelper;

	/**
	 * Instance of BlockPost.
	 *
	 * @var BlockPost
	 */
	public $instance;

	/**
	 * The expected slug.
	 *
	 * @var string
	 */
	const EXPECTED_SLUG = 'genesis_custom_block';

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		$this->instance = new BlockPost();
		$this->instance->register_controls();
		$this->instance->controls['user'] = new User();
		$this->instance->set_plugin( genesis_custom_blocks() );
	}

	/**
	 * Test register_hooks.
	 *
	 * @covers \Genesis\CustomBlocks\PostTypes\BlockPost::register_hooks()
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertEquals( 10, has_action( 'init', [ $this->instance, 'register_post_type' ] ) );
		$this->assertEquals( 10, has_action( 'admin_init', [ $this->instance, 'add_caps' ] ) );
		$this->assertEquals( 10, has_action( 'admin_init', [ $this->instance, 'row_export' ] ) );
		$this->assertEquals( 10, has_action( 'add_meta_boxes', [ $this->instance, 'add_meta_boxes' ] ) );
		$this->assertEquals( 10, has_action( 'add_meta_boxes', [ $this->instance, 'remove_meta_boxes' ] ) );
		$this->assertEquals( 10, has_action( 'post_submitbox_start', [ $this->instance, 'save_draft_button' ] ) );
		$this->assertEquals( 10, has_action( 'enter_title_here', [ $this->instance, 'post_title_placeholder' ] ) );
		$this->assertEquals( 10, has_action( 'admin_enqueue_scripts', [ $this->instance, 'enqueue_scripts' ] ) );
		$this->assertEquals( 10, has_action( 'wp_insert_post_data', [ $this->instance, 'save_block' ] ) );
		$this->assertEquals( 10, has_action( 'init', [ $this->instance, 'register_controls' ] ) );
		$this->assertEquals( 10, has_action( 'genesis_custom_blocks_field_value', [ $this->instance, 'get_field_value' ] ) );

		$this->assertEquals( 10, has_action( 'disable_months_dropdown', '__return_true' ) );
		$this->assertEquals( 10, has_action( 'page_row_actions', [ $this->instance, 'page_row_actions' ] ) );
		$this->assertEquals( 10, has_action( 'bulk_actions-edit-' . self::EXPECTED_SLUG, [ $this->instance, 'bulk_actions' ] ) );
		$this->assertEquals( 10, has_action( 'handle_bulk_actions-edit-' . self::EXPECTED_SLUG, [ $this->instance, 'bulk_export' ] ) );
		$this->assertEquals( 10, has_action( 'manage_edit-' . self::EXPECTED_SLUG . '_columns', [ $this->instance, 'list_table_columns' ] ) );
		$this->assertEquals( 10, has_action( 'manage_' . self::EXPECTED_SLUG . '_posts_custom_column', [ $this->instance, 'list_table_content' ] ) );

		$this->assertEquals( 10, has_action( 'wp_ajax_fetch_field_settings', [ $this->instance, 'ajax_field_settings' ] ) );
	}

	/**
	 * Test register_controls.
	 *
	 * @covers \Genesis\CustomBlocks\PostTypes\BlockPost::register_controls()
	 */
	public function test_register_controls() {
		$this->instance->register_controls();
		foreach ( $this->instance->controls as $control_type => $instance ) {
			$this->assertContains( 'Genesis\CustomBlocks\Blocks\Controls\\', get_class( $instance ) );
		}

		// Because the Genesis Pro subscription key isn't active, the 'user' control should not display.
		$this->assertFalse( isset( $this->instance->controls['user'] ) );

		$this->set_subscription_key_validity( true );
		genesis_custom_blocks()->admin->init();
		$this->instance->register_controls();

		// The pro subscription is active, so the 'user' and 'post' controls should be registered.
		$this->assertEquals( 'Genesis\CustomBlocks\Blocks\Controls\Post', get_class( $this->instance->controls['post'] ) );
		$this->assertEquals( 'Genesis\CustomBlocks\Blocks\Controls\Taxonomy', get_class( $this->instance->controls['taxonomy'] ) );
		$this->assertEquals( 'Genesis\CustomBlocks\Blocks\Controls\User', get_class( $this->instance->controls['user'] ) );
	}

	/**
	 * Test get_control.
	 *
	 * @covers \Genesis\CustomBlocks\PostTypes\BlockPost::get_control()
	 */
	public function test_get_control() {
		$namespace = 'Genesis\CustomBlocks\Blocks\Controls\\';
		$this->assertEquals( $namespace . 'Post', get_class( $this->instance->get_control( 'post' ) ) );
		$this->assertEquals( $namespace . 'Taxonomy', get_class( $this->instance->get_control( 'taxonomy' ) ) );
		$this->assertEquals( $namespace . 'User', get_class( $this->instance->get_control( 'user' ) ) );

		// If the control doesn't exist, this should return null.
		$this->assertEquals( null, $this->instance->get_control( 'non-existant-control' ) );
	}

	/**
	 * Test get_field_value.
	 *
	 * @covers \Genesis\CustomBlocks\PostTypes\BlockPost::get_field_value()
	 */
	public function test_get_field_value() {
		$invalid_login    = 'asdfg';
		$expected_wp_user = $this->factory()->user->create_and_get();
		$valid_id         = $expected_wp_user->ID;
		$control          = 'user';

		// Simulate the pro subscription being active.
		$this->set_subscription_key_validity( true );
		genesis_custom_blocks()->admin->init();
		$this->instance->register_controls();

		// The 'user' control.
		$this->assertEquals( false, $this->instance->get_field_value( $invalid_login, $control, false ) );
		$this->assertEquals( $expected_wp_user, $this->instance->get_field_value( [ 'id' => $valid_id ], $control, false ) );
		$this->assertEquals( '', $this->instance->get_field_value( $invalid_login, $control, true ) );
		$this->assertEquals( $expected_wp_user->get( 'display_name' ), $this->instance->get_field_value( [ 'id' => $valid_id ], $control, true ) );

		// If the pro subscription is inactive, this should still render the pro field the same as if it's active.
		$this->set_subscription_key_validity( false );
		genesis_custom_blocks()->admin->init();
		$this->instance->register_controls();

		$this->assertEquals( false, $this->instance->get_field_value( $invalid_login, $control, false ) );
		$this->assertEquals( $expected_wp_user, $this->instance->get_field_value( [ 'id' => $valid_id ], $control, false ) );
		$this->assertEquals( '', $this->instance->get_field_value( $invalid_login, $control, true ) );
		$this->assertEquals( $expected_wp_user->get( 'display_name' ), $this->instance->get_field_value( [ 'id' => $valid_id ], $control, true ) );

		// Any value for the 2nd argument other than 'user' should return the passed $value unchanged.
		$this->assertEquals( $invalid_login, $this->instance->get_field_value( $invalid_login, 'different-control', false ) );
		$this->assertEquals( $valid_id, $this->instance->get_field_value( $valid_id, 'random-control', false ) );
		$this->assertEquals( $invalid_login, $this->instance->get_field_value( $invalid_login, 'some-other-control', true ) );

		$string_value  = 'Example string';
		$array_value   = [ 'first value', 'second value' ];
		$boolean_value = true;
		$this->assertEquals( $string_value, $this->instance->get_field_value( $string_value, 'non-user-control', true ) );
		$this->assertEquals( $array_value, $this->instance->get_field_value( $array_value, 'some-control', false ) );
		$this->assertEquals( $boolean_value, $this->instance->get_field_value( $boolean_value, 'not-a-user-control', true ) );
	}

	/**
	 * Test get_capabilities.
	 *
	 * @covers \Genesis\CustomBlocks\PostTypes\BlockPost::get_capabilities()
	 */
	public function test_get_capabilities() {
		$capabilities = $this->instance->get_capabilities();
		$this->assertEquals( 'genesis_custom_block_edit_block', $capabilities['edit_post'] );
	}

	/**
	 * Test add_meta_boxes.
	 *
	 * @covers \Genesis\CustomBlocks\PostTypes\BlockPost::add_meta_boxes()
	 */
	public function test_add_meta_boxes() {
		global $wp_meta_boxes;

		$this->instance->add_meta_boxes();

		$this->assertTrue( isset( $wp_meta_boxes['genesis_custom_block']['side']['default']['block_properties'] ) );
		$this->assertTrue( isset( $wp_meta_boxes['genesis_custom_block']['normal']['default']['block_fields'] ) );
		$this->assertFalse( isset( $wp_meta_boxes['genesis_custom_block']['normal']['high']['block_template'] ) );

		$this->load_dummy_block();

		$this->instance->add_meta_boxes();

		$this->assertTrue( isset( $wp_meta_boxes['genesis_custom_block']['normal']['high']['block_template'] ) );
	}

	/**
	 * Test render_properties_meta_box.
	 *
	 * @covers \Genesis\CustomBlocks\PostTypes\BlockPost::render_properties_meta_box()
	 */
	public function test_render_properties_meta_box() {
		$this->load_dummy_block();

		ob_start();
		$this->instance->render_properties_meta_box();
		$properties_meta_box = ob_get_clean();

		$this->assertNotEmpty( $properties_meta_box );
		$this->assertGreaterThan( 0, strpos( $properties_meta_box, 'block-properties-slug' ) );
		$this->assertGreaterThan( 0, strpos( $properties_meta_box, 'block-properties-icon' ) );
		$this->assertGreaterThan( 0, strpos( $properties_meta_box, 'block-properties-category' ) );
		$this->assertGreaterThan( 0, strpos( $properties_meta_box, 'block-properties-keywords' ) );
		$this->assertGreaterThan( 0, strpos( $properties_meta_box, 'genesis_custom_block_properties_nonce' ) );
	}

	/**
	 * Test render_fields_meta_box.
	 *
	 * @covers \Genesis\CustomBlocks\PostTypes\BlockPost::render_fields_meta_box()
	 */
	public function test_render_fields_meta_box() {
		$this->load_dummy_block();

		ob_start();
		$this->instance->render_fields_meta_box();
		$fields_meta_box = ob_get_clean();

		$this->assertNotEmpty( $fields_meta_box );
		$this->assertGreaterThan( 0, strpos( $fields_meta_box, 'block-fields-list' ) );
		$this->assertGreaterThan( 0, strpos( $fields_meta_box, 'block-fields-actions-add-field' ) );
		$this->assertGreaterThan( 0, strpos( $fields_meta_box, 'genesis_custom_block_fields_nonce' ) );
	}

	/**
	 * Test render_template_meta_box.
	 *
	 * @covers \Genesis\CustomBlocks\PostTypes\BlockPost::render_template_meta_box()
	 */
	public function test_render_template_meta_box() {
		$this->load_dummy_block();

		ob_start();
		$this->instance->render_template_meta_box();
		$template_meta_box = ob_get_clean();

		$this->assertNotEmpty( $template_meta_box );
		$this->assertGreaterThan( 0, strpos( $template_meta_box, 'template-notice' ) );
		$this->assertGreaterThan( 0, strpos( $template_meta_box, 'template-location' ) );
	}

	/**
	 * Initialises a dummy block.
	 */
	public function load_dummy_block() {
		global $post;

		$block = $this->factory()->post->create(
			[
				'post_title' => 'Test Block',
				'post_type'  => self::EXPECTED_SLUG,
			]
		);

		$post = $block;
		setup_postdata( $block );
	}
}