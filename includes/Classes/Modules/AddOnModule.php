<?php

namespace WPPayForm\Classes\Modules;

use WPPayForm\Classes\View;
use WPPayForm\Classes\Helpers\Helper;

class AddOnModule 
{
	/**
	 * The Application (Framework)
	 * @var WPPayForm\Classes\Framework\Foundation\Application
	 */
	protected $app;

	public function render()
	{
		$extraMenus = [];

		$extraMenus = apply_filters('payform_addons_extra_menu', $extraMenus);

		$current_menu_item = 'payform_add_ons';

		if (isset($_GET['sub_page']) && $_GET['sub_page']) {
			$current_menu_item = sanitize_text_field($_GET['sub_page']);
        }
        
		// $this->showPayFormAddOns();
		$addOns = apply_filters('wppayform_global_addons', []);

		$addOns['slack'] = [
			'title'       => 'Slack',
			'description' => 'Get realtime notification in slack channel when a new submission will be added.',
			'logo'        => WPPAYFORM_URL.'assets/img/integrations/slack.png',
			'enabled'     => Helper::isSlackEnabled() ? 'yes' : 'no',
			'config_url'  => ''
		];

		if (!defined('WPPAYFORMPRO')) {
			$addOns = array_merge($addOns, $this->getPremiumAddOns());
	    }

		wp_localize_script('wppayform_addon_modules', 'payform_addon_modules', [
			'addons'  => $addOns
		]);

		return View::make('admin.addons.index', [
			'menus'             => $extraMenus,
			'base_url'          => admin_url('admin.php?page=payform_add_ons'),
			'current_menu_item' => $current_menu_item
		]);
	}

	public function getPremiumAddOns()
    {
        $purchaseUrl = 'https://wpmanageninja.com/downloads/fluentform-pro-add-on/';
        return array(
            'webhook'           => array(
                'title'        => 'WebHooks',
                'description'  => 'Broadcast your WP Fluent Forms Submission to any web api endpoint with the powerful webhook module.',
                'logo'         => WPPAYFORM_URL.'assets/img/integrations/webhook.png',
                'enabled'      => 'no',
                'purchase_url' => $purchaseUrl
            ),
            'zapier'            => array(
                'title'        => 'Zapier',
                'description'  => 'Connect your WP Fluent Forms data with Zapier and push data to thousands of online softwares.',
                'logo'         => WPPAYFORM_URL.'assets/img/integrations/zapier.png',
                'enabled'      => 'no',
                'purchase_url' => $purchaseUrl
            ),
            'google_sheet' => array(
                'title' => 'Google Sheet',
                'description' => 'Add WP Fluent Forms Submission to Google sheets when a form is submitted.',
                'logo' =>  WPPAYFORM_URL.'assets/img/integrations/google-sheets.png',
                'enabled' => 'no',
                'purchase_url' => $purchaseUrl
            ),
            'activecampaign'    => array(
                'title'        => 'ActiveCampaign',
                'description'  => 'WP Fluent Forms ActiveCampaign Module allows you to create ActiveCampaign list signup forms in WordPress, so you can grow your email list.',
                'logo'         => WPPAYFORM_URL.'assets/img/integrations/activecampaign.png',
                'enabled'      => 'no',
                'purchase_url' => $purchaseUrl
            ),
            'campaign_monitor'  => array(
                'title'        => 'CampaignMonitor',
                'description'  => 'WP Fluent Forms Campaign Monitor module allows you to create Campaign Monitor newsletter signup forms in WordPress, so you can grow your email list.',
                'logo'         => WPPAYFORM_URL.'assets/img/integrations/campaignmonitor.png',
                'enabled'      => 'no',
                'purchase_url' => $purchaseUrl
            ),
            'constatantcontact' => array(
                'title'        => 'ConstantContact',
                'description'  => 'Connect ConstantContact with WP Fluent Forms and create subscriptions forms right into WordPress and grow your list.',
                'logo'         => WPPAYFORM_URL.'assets/img/integrations/constantcontact.png',
                'enabled'      => 'no',
                'purchase_url' => $purchaseUrl
            ),
            'convertkit'        => array(
                'title'        => 'ConvertKit',
                'description'  => 'Connect ConvertKit with WP Fluent Forms and create subscription forms right into WordPress and grow your list.',
                'logo'         => WPPAYFORM_URL.'assets/img/integrations/convertkit.png',
                'enabled'      => 'no',
                'purchase_url' => $purchaseUrl
            ),
            'getresponse'       => array(
                'title'        => 'GetResponse',
                'description'  => 'WP Fluent Forms GetResponse module allows you to create GetResponse newsletter signup forms in WordPress, so you can grow your email list.',
                'logo'         => WPPAYFORM_URL.'assets/img/integrations/getresponse.png',
                'enabled'      => 'no',
                'purchase_url' => $purchaseUrl
            ),
            'hubspot'           => array(
                'title'        => 'Hubspot',
                'description'  => 'Connect HubSpot with WP Fluent Forms and subscribe a contact when a form is submitted.',
                'logo'         => WPPAYFORM_URL.'assets/img/integrations/hubspot.png',
                'enabled'      => 'no',
                'purchase_url' => $purchaseUrl
            ),
            'icontact'          => array(
                'title'        => 'iContact',
                'description'  => 'Connect iContact with WP Fluent Forms and subscribe a contact when a form is submitted.',
                'logo'         => WPPAYFORM_URL.'assets/img/integrations/icontact.png',
                'enabled'      => 'no',
                'purchase_url' => $purchaseUrl
            ),
            'platformly'          => array(
                'title'        => 'Platformly',
                'description'  => 'Connect Platform.ly with WP Fluent Forms and subscribe a contact when a form is submitted.',
                'logo'         => WPPAYFORM_URL.'assets/img/integrations/platformly.png',
                'enabled'      => 'no',
                'purchase_url' => $purchaseUrl
            ),
            'moosend'           => array(
                'title'        => 'MooSend',
                'description'  => 'Connect MooSend with WP Fluent Forms and subscribe a contact when a form is submitted.',
                'logo'         => WPPAYFORM_URL.'assets/img/integrations/moosend_logo.png',
                'enabled'      => 'no',
                'purchase_url' => $purchaseUrl
            ),
            'sendfox'           => array(
                'title'        => 'SendFox',
                'description'  => 'Connect SendFox with WP Fluent Forms and subscribe a contact when a form is submitted.',
                'logo'         => WPPAYFORM_URL.'assets/img/integrations/sendfox.png',
                'enabled'      => 'no',
                'purchase_url' => $purchaseUrl
            ),
            'mailerlite'        => array(
                'title'             => 'MailerLite',
                'description'       => 'Connect your WP Fluent Forms with MailerLite and add subscribers easily.',
                'logo'              => WPPAYFORM_URL.'assets/img/integrations/mailerlite.png',
                'enabled'           => 'no',
                'purchase_url'      => $purchaseUrl
            ),
            'sms_notifications' => array(
                'title' => 'SMS Notification',
                'description' => 'Send SMS in real time when a form is submitted with Twillio.',
                'logo' => WPPAYFORM_URL.'assets/img/integrations/twillio.png',
                'enabled'           => 'no',
                'purchase_url'      => $purchaseUrl
            ),
            'get_gist' => array(
                'title' => 'Gist',
                'description' => 'GetGist is Easy to use all-in-one software for live chat, email marketing automation, forms, knowledge base, and more for a complete 360° view of your contacts.',
                'logo' =>  WPPAYFORM_URL.'assets/img/integrations/getgist.png',
                'enabled' => 'no',
                'purchase_url' => $purchaseUrl
            ),
        );
    }
}