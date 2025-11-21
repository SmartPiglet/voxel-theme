<script type="text/html" id="create-post-ui-heading-field">
	<div class="ts-form-group ui-heading-field">
		<label>
			{{ field.label }}
			<div class="vx-dialog" v-if="field.description">
				<icon-info/>
				<div class="vx-dialog-content min-scroll">
					<p>{{ field.description }}</p>
				</div>
			</div>
		</label>
	</div>
</script>
