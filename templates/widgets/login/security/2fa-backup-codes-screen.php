<?php
if ( ! defined('ABSPATH') ) {
	exit;
} ?>

<div class="login-section">
	<div class="ts-login-head">
		<span class="vx-step-title"><?= _x( 'Backup Recovery Codes', 'auth', 'voxel' ) ?></span>
	</div>

	<div class="ts-form-group">
		<label><?= _x( 'Save these backup codes in a safe place! Each code can only be used once. If you lose access to your authenticator app, you can use these codes to log in.', 'auth', 'voxel' ) ?></label>
	</div>

	<div class="ts-form-group two-fa-codes" v-if="twofa.backup_codes && twofa.backup_codes.length">
		
		<div class="ts-btn ts-btn-1 ts-btn-large" v-for="code in twofa.backup_codes" :key="code">
			{{ code }}
		</div>
		
	</div>

	<div class="ts-form-group">
		<a href="#" @click.prevent="copyBackupCodes" class="ts-btn ts-btn-1">
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('ts_copy') ) ?: \Voxel\svg( 'copy-1.svg' ) ?>
			<?= _x( 'Copy codes', 'auth', 'voxel' ) ?>
		</a>
	</div>
	<div class="ts-form-group">
		<a href="#" @click.prevent="screen = 'security'" class="ts-btn ts-btn-1 ts-btn-large">
			<?= \Voxel\get_icon_markup( $this->get_settings_for_display('ts_chevron_left') ) ?: \Voxel\svg( 'chevron-left.svg' ) ?>
			<?= __( 'Go back', 'voxel' ) ?>
		</a>
	</div>
	
</div>

