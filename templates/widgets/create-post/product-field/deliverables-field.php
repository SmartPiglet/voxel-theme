<?php
if ( ! defined('ABSPATH') ) {
	exit;
}

require_once locate_template( 'templates/widgets/create-post/file-field.php' );
?>
<script type="text/html" id="product-deliverables">
	<div class="ts-form-group">
		<label>
			{{ field.label || <?= wp_json_encode( _x( 'Upload files', 'product field downloads', 'voxel' ) ) ?> }}
			<template v-if="field.description">
				<div class="vx-dialog">
					<icon-info/>
					<div class="vx-dialog-content min-scroll">
						<p v-html="field.description"></p>
					</div>
				</div>
			</template>
		</label>
		<file-upload
			v-model="files"
			:allowed-file-types="field.props.allowed_file_types.join(',')"
			:max-file-count="field.props.max_count"
		></file-upload>
	</div>
</script>
