<script type="text/html" id="create-post-time-field">
	<div class="ts-form-group vx-text-field">
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
		<div class="input-container">
			<input placeholder="Time" type="time" v-model="field.value" class="ts-filter" onfocus="this.showPicker()">
		</div>
	</div>
</script>
