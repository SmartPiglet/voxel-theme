<script type="text/html" id="create-post-title-field">
	<div class="ts-form-group">
		<label>
			{{ field.label }}

			<slot name="errors"></slot>
			<div class="vx-dialog" v-if="field.description">
				<icon-info/>
				<div class="vx-dialog-content min-scroll">
					<p>{{ field.description }}</p>
				</div>
			</div>
		</label>
		<input v-model="field.value" :placeholder="field.props.placeholder" type="text" class="ts-filter">
	</div>
</script>
