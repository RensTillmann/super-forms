<div class="super-create-form">

	<?php
	echo '<input type="hidden" name="super_skip_tutorial" value="' . get_option( 'super_skip_tutorial', false ) . '" />';

	if ( $form_id !== 0 ) {
		?>
		<div class="super-backup-history super-first-time-setup">
			<div class="super-wizard-backup-history super-wizard-settings">
				<h2><?php echo esc_html__( 'Available backups:', 'super-forms' ); ?></h2>
			</div>
			<span class="super-button super-skip-wizard"><?php echo esc_html__( 'Close', 'super-forms' ); ?></span>
			<span class="super-button super-delete-backups"><?php echo esc_html__( 'Delete all backups', 'super-forms' ); ?></span>
		</div>
		<div class="super-first-time-setup-bg"></div>
		<?php
	}
	?>

	<div class="super-wrapper">
		<div class="super-header">
			<div class="super-switch-forms">
				<i class="fas fa-chevron-down super-tooltip" title="<?php echo esc_attr__( 'Switch form', 'super-forms' ); ?>"></i>
				<ul>
					<?php
					if ( count( $forms ) == 0 ) {
						echo '<li><a href="' . esc_url( 'admin.php?page=super_create_form' ) . '">' . esc_html__( 'No forms found, create one!', 'super-forms' ) . '</a></li>';
					} else {
						foreach ( $forms as $value ) {
							if ( $form_id != $value->ID ) {
								echo '<li value="' . $value->ID . '"><a href="' . esc_url( 'admin.php?page=super_create_form&id=' . $value->ID ) . '">' . esc_html( $value->post_title ) . '</a></li>';
							}
						}
					}
					?>
				</ul>
			</div>
			<input type="text" name="title" class="form-name super-tooltip" title="<?php echo esc_attr__( 'Enter a name for your form', 'super-forms' ); ?>" value="<?php echo $title; ?>" />
			<?php
			if ( isset( $_GET['id'] ) ) {
				echo '<input data-modified-time="' . get_post_modified_time( 'U', false, $_GET['id'] ) . '" type="text" readonly="readonly" class="super-get-form-shortcodes super-tooltip" title="' . esc_attr__( 'Paste shortcode on any page', 'super-forms' ) . '" value=\'[super_form id="' . absint( $form_id ) . '"]\' />';
				echo '<input type="hidden" name="form_id" value="' . absint( $form_id ) . '" />';
			} else {
				echo '<input data-modified-time="0" type="text" readonly="readonly" class="super-get-form-shortcodes super-tooltip" title="' . esc_attr__( 'Please save your form first!', 'super-forms' ) . '" value="[form-not-saved-yet]" />';
				echo '<input type="hidden" name="form_id" value="" />';
			}
			echo '<p>' . esc_html__( 'Take the shortcode and place it anywere!', 'super-forms' ) . '</p>';
			echo '<div class="super-actions">';
				echo '<span class="super-save super-tooltip" title="' . esc_attr__( 'Save your form', 'super-forms' ) . '" ><i class="fas fa-save"></i>' . esc_html__( 'Save', 'super-forms' ) . '</span>';
				echo '<span class="super-clear super-tooltip" title="' . esc_attr__( 'Start all over', 'super-forms' ) . '" ><i class="fas fa-eraser"></i>' . esc_html__( 'Clear', 'super-forms' ) . '</span>';
				echo '<span class="super-delete super-tooltip" title="' . esc_attr__( 'Delete complete form', 'super-forms' ) . '" ><i class="fas fa-trash-alt"></i>' . esc_html__( 'Delete', 'super-forms' ) . '</span>';
				echo '<span class="super-preview super-desktop super-tooltip super-active" title="' . esc_attr__( 'Desktop preview', 'super-forms' ) . '" ><i class="fas fa-desktop"></i></span>';
				echo '<span class="super-preview super-tablet super-tooltip" title="' . esc_attr__( 'Tablet preview', 'super-forms' ) . '" ><i class="fas fa-tablet"></i></span>';
				echo '<span class="super-preview super-mobile super-tooltip" title="' . esc_attr__( 'Mobile preview', 'super-forms' ) . '" ><i class="fas fa-mobile"></i></span>';
				echo '<span class="super-preview super-switch super-tooltip" title="' . esc_attr__( 'Live preview', 'super-forms' ) . '" >' . esc_html__( 'Preview', 'super-forms' ) . '</span>';
				echo '<label><input type="checkbox" name="allow_duplicate_names" /><i>' . esc_html__( 'Allow saving form with duplicate field names (for developers only)', 'super-forms' ) . '</i></label>';
			echo '</div>';
			?>
		</div>
		<div class="super-builder">

			<?php
			echo SUPER_Common::load_google_fonts( $settings );
			// Try to load the selected theme style
			// Always load the default styles
			$style_content  = require SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/style-default.php';
			$style_content .= require SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/fonts.php';
			$style_content .= require SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/colors.php';
			$class          = ' super-default-squared';
			if ( ! empty( $settings['theme_style'] ) ) {
				$class = ' ' . $settings['theme_style'];
			}
			// @since 2.9.0 - theme field size in height
			if ( ! isset( $settings['theme_field_size'] ) ) {
				$settings['theme_field_size'] = 'medium';
			}
			$class .= ' super-field-size-' . $settings['theme_field_size'];
			// @since 1.2.4     - use transparent field background
			if ( ! empty( $settings['theme_field_transparent'] ) ) {
				$class .= ' super-transparent-fields';
			}
			$theme_style = $class;

			?>

			<div class="super-preview"> 
				<?php
				if ( $form_id == 0 ) {
					echo '<div class="super-demos-notice">';
					echo '<h1>' . esc_html__( 'What\'s new?', 'super-forms' ) . '</h1>';
					echo '<h2>' . sprintf( esc_html__( 'Listings Add-on', 'super-forms' ), '<span style="color:red;">', '</span>' ) . '</h2>';
					echo '<p><a target="_blank" href="https://docs.super-forms.com/features/integrations/listings" class="button button-secondary button-large">' . esc_html__( 'Documentation', 'super-forms' ) . '</a> <a target="_blank" href="' . esc_url( admin_url() . 'admin.php?page=super_addons' ) . '" class="button button-primary button-large">' . esc_html__( 'Start 15 day trial', 'super-forms' ) . '</a></p>';
					echo '<hr />';
					echo '<h2>' . sprintf( esc_html__( 'PDF Generator Add-on', 'super-forms' ), '<span style="color:red;">', '</span>' ) . '</h2>';
					echo '<p><a target="_blank" href="https://docs.super-forms.com/features/integrations/pdf-generator" class="button button-secondary button-large">' . esc_html__( 'Documentation', 'super-forms' ) . '</a> <a target="_blank" href="' . esc_url( admin_url() . 'admin.php?page=super_addons' ) . '" class="button button-primary button-large">' . esc_html__( 'Start 15 day trial', 'super-forms' ) . '</a></p>';
					echo '<hr />';
					echo '<h2>' . sprintf( esc_html__( 'Secure File Uploads', 'super-forms' ), '<span style="color:red;">', '</span>' ) . '</h2>';
					echo '<p>' . sprintf( esc_html__( 'By default any files uploaded via your forms will no longer be visible in the %1$sMedia Library%2$s. To change this behaviour you can visit the File Upload Settings.', 'super-forms' ), '<a target="_blank" href="' . esc_url( get_admin_url() . 'upload.php' ) . '">', '</a>' ) . '</p>';
					echo '<p><a target="_blank" href="https://docs.super-forms.com/quick-start/secure-file-uploads" class="button button-secondary button-large">' . esc_html__( 'Documentation', 'super-forms' ) . '</a> <a target="_blank" href="' . esc_url( admin_url() . 'admin.php?page=super_settings#file-upload-settings' ) . '" class="button button-primary button-large">' . esc_html__( 'Change File Upload Settings', 'super-forms' ) . '</a></p>';
					echo '</div>';
				}

				echo '<div class="super-form-history">';
				echo '<span class="super-maximize-toggle super-tooltip" title="' . esc_attr__( 'Maximize all elements', 'super-forms' ) . '"></span>';
				echo '<span class="super-minimize-toggle super-tooltip" title="' . esc_attr__( 'Minimize all elements', 'super-forms' ) . '"></span>';
				if ( $form_id != 0 ) {
					echo '<span class="super-backups super-tooltip" title="' . esc_attr__( 'Restore a previous saved version of this Form', 'super-forms' ) . '"></span>';

				}
				echo '<span class="super-redo super-tooltip super-disabled" title="' . esc_attr__( 'Redo last change', 'super-forms' ) . '"></span>';
				echo '<span class="super-undo super-tooltip super-disabled" title="' . esc_attr__( 'Undo last change', 'super-forms' ) . '"></span>';
				echo '</div>';

				$current_tab = 'builder';
				if ( ! empty( $_GET['tab'] ) ) {
					$current_tab = $_GET['tab'];
				}
				$tabs         = array(
					'builder'      => esc_html__( 'Builder', 'super-forms' ),
					'emails'       => esc_html__( 'Emails', 'super-forms' ),
					'settings'     => esc_html__( 'Settings', 'super-forms' ),
					'theme'        => esc_html__( 'Theme', 'super-forms' ),
					'translations' => esc_html__( 'Translations', 'super-forms' ),
					'secrets'      => esc_html__( 'Secrets', 'super-forms' ),
					'triggers'     => esc_html__( 'Triggers', 'super-forms' ),
				);
				$tabs         = apply_filters( 'super_create_form_tabs', $tabs );
				$tabs['code'] = esc_html__( 'Code', 'super-forms' );

				$tabs_content = '';
				echo '<div class="super-tabs">';
				foreach ( $tabs as $k => $v ) {
					echo '<span class="super-tab-' . $k . ( $current_tab == $k ? ' super-active' : '' ) . '" data-tab="' . esc_attr( $k ) . '" data-title="' . esc_attr( $v ) . '">';
					echo esc_html( $v );
					if ( ! is_array( $translations ) ) {
						$translations = array();
					}
					if ( $k === 'builder' && ! empty( $translations ) && current( $translations ) ) {
						echo '<img src="' . esc_url( SUPER_PLUGIN_FILE . 'assets/images/blank.gif' ) . '" class="flag flag-' . current( $translations )['flag'] . '" />';
					}
					echo '</span>';
					ob_start();
					echo '<div class="super-tab-content super-tab-' . $k . ( $current_tab == $k ? ' super-active' : '' ) . '">';
					// Actions:
					// super_create_form_`builder`_tab
					// super_create_form_`emails`_tab
					// super_create_form_`settings`_tab
					// super_create_form_`theme`_tab
					// super_create_form_`translations`_tab
					// super_create_form_`triggers`_tab
					// super_create_form_`code`_tab
					// error_log('stripe1: '.json_encode($stripe));
					// error_log('pdf1: '.json_encode($pdf));
					do_action(
						'super_create_form_' . $k . '_tab',
						array(
							'version'       => $version,
							'form_id'       => $form_id,
							'secrets'       => array(
								'local'  => $localSecrets,
								'global' => $globalSecrets,
							),
							'shortcodes'    => $shortcodes,
							'settings'      => $settings,
							'woocommerce'   => $woocommerce,
							'listings'      => $listings,
							'pdf'           => $pdf,
							'stripe'        => $stripe,
							'translations'  => $translations,
							'theme_style'   => $theme_style,
							'style_content' => $style_content,
						)
					);
					echo '</div>';
					$tabs_content .= ob_get_contents();
					ob_end_clean();
				}
				echo '</div>';
				echo '<div class="super-tabs-content">';
					// Display content of all tabs
					echo $tabs_content;
				echo '</div>';
				?>

				<div class="super-live-preview"></div>
			</div>
			<!--<div class="super-ui-resize-bar"></div>-->
			<div class="super-elements super-active">
				<?php
				echo '<div class="super-element super-element-settings">';
					echo '<h3><i class="fas fa-th-large"></i>' . esc_html__( 'Element Settings & Options', 'super-forms' ) . '</h3>';
					echo '<div class="super-elements-container"><p>' . sprintf( esc_html__( 'You are currently not editing an element.%1$sEdit any alement by clicking the %2$s icon.', 'super-forms' ), '<br />', '<i class="fas fa-pencil-alt"></i>' ) . '</p></div>';
				echo '</div>';
				foreach ( $shortcodes as $k => $v ) {
					echo '<div class="super-element ' . $v['class'] . '">';
						echo '<h3><i class="fas fa-th-large"></i>' . $v['title'] . '</h3>';
						echo '<div class="super-elements-container">';
					if ( isset( $v['info'] ) ) {
						echo '<p>' . $v['info'] . '</p>';
					}
					foreach ( $v['shortcodes'] as $key => $value ) {
						if ( ( ! isset( $value['hidden'] ) ) || ( $value['hidden'] == false ) ) {
							echo SUPER_Shortcodes::output_element( $key, $k, $value );
						}
					}
						echo '</div>';
					echo '</div>';
				}
				?>
				<div class="super-element super-form-settings">
					<h3><i class="fas fa-th-large"></i><?php echo esc_html__( 'Form Settings', 'super-forms' ); ?></h3>
					<div class="super-elements-container">
						<?php
						echo '<div class="super-form-settings-tabs">';
							echo '<select>';
							$i = 0;
						foreach ( $fields as $key => $value ) {
							if ( ( ( ! isset( $value['hidden'] ) ) || ( $value['hidden'] == false ) || ( $value['hidden'] === 'settings' ) ) && ( ! empty( $value['name'] ) ) ) {
								echo '<option value="' . $i . '" ' . ( $i == 0 ? 'selected="selected"' : '' ) . '>' . $value['name'] . '</option>';
								++$i;
							}
						}
							echo '</select>';
							echo SUPER_Common::reset_setting_icons(
								array(
									'default' => '_reset_',
									'g'       => '_reset_',
									'v'       => '_reset_',
								)
							);
							echo '</div>';

							$counter = 0;
							foreach ( $fields as $key => $value ) {
								if ( ( ( ! isset( $value['hidden'] ) ) || ( $value['hidden'] == false ) || ( $value['hidden'] === 'settings' ) ) && ( ! empty( $value['name'] ) ) ) {
									++$counter;
									echo '<div class="tab-content ' . ( $counter == 1 ? 'super-active' : '' ) . '">';
									if ( isset( $value['html'] ) ) {
										foreach ( $value['html'] as $v ) {
											echo $v;
										}
									}
									echo SUPER_Common::docs( $value );
									if ( isset( $value['fields'] ) ) {
										foreach ( $value['fields'] as $k => $v ) {
											if ( ( ! isset( $v['hidden'] ) ) || ( $v['hidden'] == false ) ) {
												if ( ! isset( $v['type'] ) ) {
													$v['type'] = 'text';
												}
												$filter      = '';
												$parent      = '';
												$filtervalue = '';
												if ( ( isset( $v['filter'] ) ) && ( $v['filter'] == true ) ) {
													$filter = ' super-filter';
													if ( isset( $v['parent'] ) ) {
														$parent = ' data-parent="' . $v['parent'] . '"';
													}
													if ( isset( $v['filter_value'] ) ) {
														$filtervalue = ' data-filtervalue="' . $v['filter_value'] . '"';
													}
												}
												echo '<div class="super-field super-field-' . $k . $filter;
												if ( $v['type'] !== 'multicolor' ) {
													// If locked to global value `_g_` then add class
													$v['lockToGlobalSetting'] = false;
													if ( $v['v'] === '_g_' ) {
														$v['v']                   = $v['g'];
														$v['lockToGlobalSetting'] = true;
														echo ' _g_';
													}
												}
												echo '"' . $parent . $filtervalue . '>';
												if ( isset( $v['name'] ) ) {
													echo '<div class="super-field-name">' . ( $v['name'] );
													if ( $v['type'] !== 'checkbox' ) {
														if ( isset( $v['desc'] ) ) {
															echo '<i class="info super-tooltip" title="' . esc_attr( $v['desc'] ) . '"></i>';
														}
													}
												}
												if ( isset( $v['label'] ) ) {
													echo '<div class="super-field-label">' . nl2br( $v['label'] );
													if ( $v['type'] !== 'checkbox' ) {
														if ( ! isset( $v['name'] ) && isset( $v['desc'] ) ) {
															echo '<i class="info super-tooltip" title="' . esc_attr( $v['desc'] ) . '"></i>';
														}
													}
												}
												if ( isset( $v['label'] ) ) {
													echo '</div>';
												}
												if ( isset( $v['name'] ) ) {
													echo '</div>';
												}
												echo SUPER_Common::docs( $v );
												echo '<div class="super-field-input">';
												if ( $v['type'] === 'multicolor' ) {
													foreach ( $v['colors'] as $ck => $cv ) {
														if ( isset( $settings[ $ck ] ) ) {
															$v['colors'][ $ck ]['v'] = $settings[ $ck ];
														}
													}
												}
													echo call_user_func( array( 'SUPER_Field_Types', $v['type'] ), $k, $v );
												echo '</div>';
												echo '</div>';
											}
										}
									}
									echo '</div>';
								}
							}
							?>
					</div>
				</div>
				
			</div>
		</div>
	</div>
</div>
