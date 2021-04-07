<template>
	<div v-loading="loading" class="wpf_payment_wrapper">
		<div class="wpf_payment_settings_wrapper">
			<el-tabs v-model="selectedMethod" type="border-card">
				<el-tab-pane name="stripe-settings" label="Stripe Settings">
					<stripe-settings v-if="selectedMethod == 'stripe-settings'"
					/>
				</el-tab-pane>
				<el-tab-pane name="paypal-settings" label="PayPal Settings">
					<pay-pal-settings v-if="selectedMethod == 'paypal-settings'"/>
				</el-tab-pane>
				<el-tab-pane name="coupons" label="Coupons">
					<coupons v-if="selectedMethod == 'coupons'"/>
				</el-tab-pane>
			</el-tabs>
		</div>
	</div>
</template>
<script type="text/babel">
	import PayPalSettings from './PayPalSettings';
	import StripeSettings from './StripeSettings';
	import Coupons from "./Coupons";

	export default {
		name: 'Payments',
		props: ['settings'],
		components: {
			StripeSettings,
			PayPalSettings,
			Coupons
		},
		data() {
			return {
				loading: false,
				selectedMethod: 'stripe-settings'
			};
		},
		mounted() {
            jQuery('li.wpf_menu_payments').addClass('active');
            let uri = window.location.href;
			let path = uri.substring(uri.lastIndexOf("/payments") + 10, uri.length);
            if(path) {
                this.selectedMethod = path;
            }
        }
	};
</script>

<style lang="scss">
	.wpf_pre_settings_wrapper {
		text-align: center;
		padding: 20px 50px 50px;
		max-width: 800px;
		margin: 50px auto;
		background: #f1f1f1;
		border-radius: 20px;
		h2 {
			line-height: 36px;
			font-size: 26px;
		}
	}
	.wpf_payment_wrapper {

		padding: 30px;
	}
</style>
