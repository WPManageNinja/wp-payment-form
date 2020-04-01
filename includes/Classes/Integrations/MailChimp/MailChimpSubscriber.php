<?php

namespace WPPayForm\Classes\Integrations\MailChimp;

use WPPayForm\Classes\ConditionAssesor;
use WPPayForm\Classes\Integrations\LogResponseTrait;
use WPPayForm\Framework\Helpers\ArrayHelper;

trait MailChimpSubscriber
{
	use LogResponseTrait;

	/**
	 * Enabled MailChimp feed settings.
	 *
	 * @var array $feeds
	 */
	protected $feeds = [];

	/**
	 * Required for api response logging
	 * @var string
	 */
	protected $metaKey = 'fluentform_mailchimp_feed';

	/**
	 * Form input data.
	 *
	 * @param array $formData
	 */
	public function setApplicableFeeds($formData)
	{
			$feeds = $this->getAll();

			foreach ($feeds as $feed) {
					if ($this->isApplicable($feed, $formData)) {
							$email = ArrayHelper::get(
									$formData, ArrayHelper::get($feed->formattedValue, 'fieldEmailAddress')
							);

							if (is_string($email) && is_email($email)) {
									$feed->formattedValue['fieldEmailAddress'] = $email;

									$this->feeds[] = $feed;
							}
					}
			}
	}

	/**
	 * Determine if the feed is eligible to be applied.
	 *
	 * @param $feed
	 * @param $formData
	 *
	 * @return bool
	 */
	public function isApplicable(&$feed, &$formData)
	{
			return ArrayHelper::get($feed->formattedValue, 'enabled') &&
					ArrayHelper::get($feed->formattedValue, 'list_id') &&
					ConditionAssesor::evaluate($feed->formattedValue, $formData);
	}

	/**
	 * Subscribe a user to the list on form submission.
	 *
	 * @param $formData
	 */
	public function subscribe($feed, $formData, $entry, $form)
	{


			$feedData = $feed['processedValues'];


			if (!is_email($feedData['fieldEmailAddress'])) {
					$feedData['fieldEmailAddress'] = ArrayHelper::get($formData, $feedData['fieldEmailAddress']);
			}

			if (!is_email($feedData['fieldEmailAddress'])) {
					return false;
			}

			$mergeFields = array_filter(ArrayHelper::get($feedData, 'merge_fields'));
			$status = ArrayHelper::isTrue($feedData, 'doubleOptIn') ? 'pending' : 'subscribed';

			$listId = $feedData['list_id'];

			$arguments = [
					'email_address' => $feedData['fieldEmailAddress'],
					'status'        => $status,
					'double_optin'  => ArrayHelper::isTrue($feedData, 'doubleOptIn'),
					'vip'           => ArrayHelper::isTrue($feedData, 'markAsVIP'),
			];

			if($mergeFields) {
					$arguments['merge_fields'] = (object)$mergeFields;
			}

			if($entry->ip) {
					$arguments['ip_signup'] = $entry->ip;
			}

			if ($feedData['tags']) {
					$providedTags = explode(',', $feedData['tags']);
					$arguments['tags'] = array_map( 'trim', $providedTags );
			}

			$note = '';
			if ($feedData['note']) {
					$note = esc_attr($feedData['note']);
			}

			$arguments['interests'] = [];

			$contactHash = md5(strtolower( $arguments['email_address'] ));

			$existingMember = $this->getMemberByEmail($listId, $arguments['email_address']);

			if(!empty($existingMember['id'])) {
					// We have members so we can merge the values
					if(apply_filters('fluentform_mailchimp_keep_existing_interests', true, $form->id)) {
							$arguments['interests'] = ArrayHelper::get($existingMember, 'interests', []);
					}

					if($arguments['tags']) {
							if(apply_filters('fluentform_mailchimp_keep_existing_tags', true, $form->id)) {
									$tags = ArrayHelper::get($existingMember, 'tags', []);
									$tagNames = [];
									foreach ($tags as $tag) {
											$tagNames[] = $tag['name'];
									}
									$allTags = wp_parse_args($arguments['tags'], $tagNames);
									$arguments['tags'] = array_unique($allTags);
							}
					}
			}

			if(
					ArrayHelper::get($feedData, 'interest_group.sub_category') &&
					ArrayHelper::get($feedData, 'interest_group.category')
			) {
					$interestGroup = ArrayHelper::get($feedData, 'interest_group.sub_category');
					$arguments['interests'][$interestGroup] = true;
			}

			$arguments = array_filter($arguments);
			$settings = get_option('_fluentform_mailchimp_details');
			$MailChimp = new MailChimp($settings['apiKey']);
			$endPoint = 'lists/' . $listId . '/members/'.$contactHash;

			$result = $MailChimp->put($endPoint, $arguments);

			if($result && !is_wp_error($result) && isset($result['id'])) {
					$noteEnpoint = 'lists/' . $listId . '/members/' . $contactHash . '/notes';
					if($note) {
							$MailChimp->post($noteEnpoint, [
									'note' => $note
							]);
					}
					return true;
			}
			return $result;
	}

	/**
	 * Get a specific MailChimp list member.
	 *
	 */
	public function getMemberByEmail( $list_id, $email_address ) {
			$settings = get_option('_fluentform_mailchimp_details');
			$MailChimp = new MailChimp($settings['apiKey']);
			// Prepare subscriber hash.
			$subscriber_hash = md5( strtolower( $email_address ) );
			return $MailChimp->get( 'lists/' . $list_id . '/members/' . $subscriber_hash );
	}
}
