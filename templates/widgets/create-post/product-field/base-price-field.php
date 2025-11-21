<?php
if ( ! defined('ABSPATH') ) {
	exit;
} ?>
<script type="text/html" id="product-base-price">
	<div class="ts-form-group" :class="{'vx-1-2': field.props.discount_price.enabled}">
		<label>
			{{ field.label || <?= wp_json_encode( _x( 'Price', 'product field', 'voxel' ) ) ?> }}
			<template v-if="field.description">
				<div class="vx-dialog">
					<icon-info/>
					<div class="vx-dialog-content min-scroll">
						<p v-html="field.description"></p>
					</div>
				</div>
			</template>
		</label>
		<div class="input-container">
			<input
				type="number" class="ts-filter" v-model="value.amount" min="0"
				placeholder="<?= esc_attr( _x( 'Add price', 'product field', 'voxel' ) ) ?>"
			>
			<span class="input-suffix"><?= \Voxel\get_primary_currency() ?></span>
		</div>
	</div>

	<div v-if="field.props.discount_price.enabled" class="ts-form-group vx-1-2">
		<label><?= _x( 'Discount price', 'product field', 'voxel' ) ?></label>
		<div class="input-container">
			<input
				type="number" class="ts-filter" v-model="value.discount_amount" min="0"
				placeholder="<?= esc_attr( _x( 'Add price', 'product field', 'voxel' ) ) ?>"
			>
			<span class="input-suffix"><?= \Voxel\get_primary_currency() ?></span>
		</div>
	</div>
</script>