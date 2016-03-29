<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// widget to make an appointment

class mc_submissions_widget extends WP_Widget {

	function __construct() {
		parent::__construct( false,$name=__('My Calendar: Submit an Event','my-calendar-submissions'), array( 'customize_selective_refresh' => true ) );
	}
	// Creates the 
	function widget($args, $instance) {
		extract($args);
		$the_title = apply_filters( 'widget_title', $instance['title'], $instance, $args );
		$fields = ( isset($instance['fields'] ) )?$instance['fields']:array();
		$location_fields = ( isset($instance['location_fields'] ) ) ? $instance['location_fields'] : array();
		$category = ( isset($instance['category'] ) )?esc_attr($instance['category']) : 1; // set defaults
		$location = ( isset($instance['location'] ) )?esc_attr($instance['location']) : 1;
		$categories = ( isset($instance['categories'] ) ) ? esc_attr( $instance['categories'] ) : 'false'; // are they enabled at all
		$locations = ( isset($instance['locations'] ) ) ? esc_attr( $instance['locations'] ) : 'false';	
		$the_form = mc_submit_form( $fields,$categories,$locations,$category,$location,$location_fields );
		
		if ( mcs_user_can_submit_events() ) {
			echo $before_widget;
			echo ( $the_title )?$before_title . $the_title . $after_title:'';
			echo $the_form;
			echo $after_widget;
		}
	}

	function form($instance) {
		mcs_check();
		$options = get_option( 'mcs_options' );
		$fields = $options['fields'];
		$location_fields = $options['location_fields'];
		if ( empty( $fields ) ) {
			$options = mcs_default_settings( false );
			$fields = $options['fields'];
			$location_fields = $options['location_fields'];
		}
		$defaults = $options['widget_defaults'];
		$widget_title = ( !empty($instance['title']) ) ? esc_attr($instance['title']) : $defaults['title'];
		if ( !empty($instance) ) { 
			$widget_fields = ( empty($instance['fields']) ) ? $fields : $instance['fields']; 
		} else { 
			$widget_fields = $fields; 
		}
		if ( !empty($instance) ) { 
			$widget_location_fields = ( empty($instance['location_fields']) ) ? $location_fields : $instance['location_fields'];
		} else { 
			$widget_location_fields = $location_fields; 
		}
		$widget_categories = ( !empty($instance['categories']) ) ? esc_attr($instance['categories']) : '';
		$widget_category = ( !empty($instance['category']) )?esc_attr($instance['category']):'';
		$widget_locations = ( !empty($instance['locations']) )?esc_attr($instance['locations']):'';
		$widget_location = ( !empty($instance['location']) )?esc_attr($instance['location']):'';
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title','my-calendar-submissions'); ?>:</label><br />
			<input class="widefat" type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php esc_attr_e( $widget_title ); ?>"/>
		</p>
		<fieldset>
			<legend><strong><?php _e('Included Fields','my-calendar-submissions'); ?></strong></legend>
			<ul>
				<li><?php _e('Event Title (required)','my-calendar-submissions'); ?></li>
				<li><?php _e('Date/Time (required)','my-calendar-submissions'); ?></li>
				<li><?php _e('Name','my-calendar-submissions'); ?></li>
				<li><?php _e('Email (required)','my-calendar-submissions'); ?></li>
				<?php
					$checked=" checked='checked'";
					// can select: each field to require (title and date must be included)
					if ( is_array($fields) ) {
						foreach ( $fields as $key=>$value ) {
							$check = ( in_array( $value, $widget_fields ) ) ? $checked : '';
							echo "<li><input type='checkbox' name='".$this->get_field_name('fields')."[$key]' id='".$this->get_field_id('title')."mc_$key' value='$value'$check /> <label for='".$this->get_field_id('title')."mc_$key'>" . esc_html( $value ) . "</label></li>\n";
						}
					}
				?>
			</ul>	
		</fieldset>
		<fieldset>
			<legend><strong><?php _e('Categories','my-calendar-submissions'); ?></strong></legend>
			<p>
				<input type="checkbox" name="<?php echo $this->get_field_name('categories'); ?>" id="<?php echo $this->get_field_name('categories'); ?>" value="true"<?php echo ($widget_categories=='true')?$checked:''; ?>> <label for="<?php echo $this->get_field_name('categories'); ?>"><?php _e('Include list of categories','my-calendar-submissions'); ?></label>
			</p>
			<p>
				<select name="<?php echo $this->get_field_name('category'); ?>" id="<?php echo $this->get_field_name('category'); ?>">
				<?php echo mc_category_select( $widget_category ); ?>
				</select> <label for="<?php echo $this->get_field_name('category'); ?>"><?php _e('Default category','my-calendar-submissions'); ?></label>
			</p>
		</fieldset>
		<fieldset>
			<legend><strong><?php _e('Locations','my-calendar-submissions'); ?></strong></legend>
			<ul>
			<li><input type="radio" name="<?php echo $this->get_field_name('locations'); ?>" id="<?php echo $this->get_field_id('locations'); ?>choose" value="choose"<?php echo ($widget_locations=='choose')?$checked:''; ?>> <label for="<?php echo $this->get_field_id('locations'); ?>choose"><?php _e('Can choose a location','my-calendar-submissions'); ?></label></li>
			<li><input type="radio" name="<?php echo $this->get_field_name('locations'); ?>" id="<?php echo $this->get_field_id('locations'); ?>either" value="either"<?php echo ($widget_locations=='either')?$checked:''; ?>> <label for="<?php echo $this->get_field_id('locations'); ?>either"><?php _e('Can enter or choose a location','my-calendar-submissions'); ?></label></li>
			<li><input type="radio" name="<?php echo $this->get_field_name('locations'); ?>" id="<?php echo $this->get_field_id('locations'); ?>enter" value="enter"<?php echo ($widget_locations=='enter')?$checked:''; ?>> <label for="<?php echo $this->get_field_id('locations'); ?>enter"><?php _e('Can enter a location','my-calendar-submissions'); ?></label></li>
			<li><input type="radio" name="<?php echo $this->get_field_name('locations'); ?>" id="<?php echo $this->get_field_id('locations'); ?>neither" value="neither"<?php echo ($widget_locations=='neither')?$checked:''; ?>> <label for="<?php echo $this->get_field_id('locations'); ?>neither"><?php _e('None of the above','my-calendar-submissions'); ?></label></li>
			</ul>
			<p>
			<label for="<?php echo $this->get_field_name('location'); ?>"><?php _e('Default location','my-calendar-submissions'); ?></label> <select name="<?php echo $this->get_field_name('location'); ?>" id="<?php echo $this->get_field_name('location'); ?>">
				<option value=''><?php _e( 'None', 'my-calendar-submissions' ); ?></option>
				<?php echo mc_location_select( $widget_location ); ?>
			</select>
			</p>
		</fieldset>
		<fieldset>
			<legend><strong><?php _e('Included Location Fields','my-calendar-submissions'); ?></strong></legend>
			<ul>
			<li><?php _e('Location Label (required)','my-calendar-submissions'); ?></li>
			<?php
			// can select: each field to require (label must be included)
				if ( is_array($location_fields) ) {
					foreach ( $location_fields as $key=>$value ) {
						$check = ( in_array($value, $widget_location_fields) )?$checked:'';
						echo "<li><input type='checkbox' name='".$this->get_field_name('location_fields')."[$key]' id='".$this->get_field_id('location_fields')."mc_$key' value='$value'$check /> <label for='".$this->get_field_id('location_fields')."mc_$key'>$value</label></li>\n";
					}
				}
			?>
			</ul>
		</fieldset>	
		<?php
	} 

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = isset( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['fields'] = isset( $new_instance['fields'] ) ? $new_instance['fields'] : array();
		$instance['location_fields'] = isset( $new_instance['location_fields'] ) ? $new_instance['location_fields'] : array();
		$instance['category'] = isset( $new_instance['category'] ) ? $new_instance['category'] : '';
		$instance['categories'] = isset( $new_instance['categories'] ) ? $new_instance['categories'] : false;
		$instance['location'] = isset( $new_instance['location'] ) ? $new_instance['location'] : false;
		$instance['locations'] = isset( $new_instance['locations'] ) ? $new_instance['locations'] : false;
		return $instance;		
	}
}