<div class="element-settings-wrapper">
	<table class="element-settings-table">
		<?php
		$elements = SUPER_Shortcodes::shortcodes();
		foreach( $elements as $gk => $gv ) {
			$type = $gv['title'];
			if($type=='Layout Elements') continue;
			if( !isset($current_type) ) $current_type = '';
			foreach( $gv['shortcodes'] as $k => $v ) {
				if(isset($v['predefined'])) continue;
				if( $current_type==$type ) {
					$new_type = '';
				}else{
					$new_type = $type;
				}
				$i = 0;
				echo '<tr>';
					echo '<th style="width: 150px;">Field name</th>';
					echo '<th style="width: 150px;">Setting group</th>';
					echo '<th style="width: 150px;">Option</th>';
					echo '<th>Description</th>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>' . $v['name'] . '</td>';
				foreach( $v['atts'] as $sk => $sv ) {
					foreach( $sv['fields'] as $fk => $fv ) {
						if( $i==0 ) {
							echo '<td>' . ( ( (isset($prev_group)) && ($prev_group==$sv['name']) ) ? '' : '<em>' . $sv['name'] . '</em>' ) . '</td>';
							echo '<td>' . $fk . '</td>';
							if(isset($fv['doc'])) {
								echo '<td>' . $fv['doc'] . '</td>';
							}else{
								echo '<td>' . (isset($fv['name']) ? $fv['name'] . '<br />' : '') . (isset($fv['label']) ? $fv['label'] . '<br />' : '') . (isset($fv['desc']) ? $fv['desc'] . '<br />' : '') . '</td>';
							}
							echo '</tr>';
						}else{
							if( (!isset($fv['name'])) && (!isset($fv['label'])) && (!isset($fv['desc'])) ) {
								if( (isset($fv['type'])) && ($fv['type']=='checkbox') ) {}else{continue;}
							}
							if( ( ( (isset($prev_group)) && ($prev_group==$sv['name']) ) ? '' : '<em>' . $sv['name'] . '</em>' ) ) {
								echo '<tr>';
									echo '<th style="width: 150px;">Field name</th>';
									echo '<th style="width: 150px;">Setting group</th>';
									echo '<th style="width: 150px;">Option</th>';
									echo '<th>Description</th>';
								echo '</tr>';
							}
							echo '<tr>';
							echo '<td>' . ( ( (isset($prev_group)) && ($prev_group==$sv['name']) ) ? '' : $v['name'] ) . '</td>';
							echo '<td>' . ( ( (isset($prev_group)) && ($prev_group==$sv['name']) ) ? '' : '<em>' . $sv['name'] . '</em>' ) . '</td>';
							echo '<td>' . $fk . '</td>';
							if(isset($fv['doc'])) {
								echo '<td>' . $fv['doc'] . '</td>';
							}else{
								if( (!isset($fv['name'])) && (!isset($fv['label'])) && (!isset($fv['desc'])) ) {
									if( (isset($fv['type'])) && ($fv['type']=='checkbox') ) {
										echo '<td>';
										foreach($fv['values'] as $cbk => $cbv){
											echo $cbk . ' - ' . $cbv . '<br />';
										}
										echo '</td>';
									}else{
										echo '<td> - </td>';
									}
								}else{
									echo '<td>' . (isset($fv['name']) ? $fv['name'] . '<br />' : '') . (isset($fv['label']) ? $fv['label'] . '<br />' : '') . (isset($fv['desc']) ? $fv['desc'] . '<br />' : '') . '</td>';
								}
							}
							echo '</tr>';
						}
						$prev_group = $sv['name'];
						$i++;
					}
				}
				$current_type = $type;
			}
		}
		?>
	</table>
</div>