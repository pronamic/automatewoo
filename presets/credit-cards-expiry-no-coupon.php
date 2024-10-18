<?php

$email_content = <<<EMAIL
Hi {{ customer.first_name | fallback: 'there' }},

The credit card you have on file is expiring next week. In order to avoid problems or interruptions, please update your billing information with an active card.

<a href="{{ shop.url }}" class="automatewoo-button">Click here to update your account</a>.

See you soon,
Your friends at {{ shop.title }}
EMAIL;

return [
	'title'       => 'Credit cards: Expiry reminder',
	'description' => 'Trigger an email to customers reminding them that a saved credit card will expire soon.',
	'type'        => 'automatic',
	'trigger'     => [
		'name'    => 'customer_before_saved_card_expiry',
		'options' => [
			'days_before_expiry' => '7',
		],
	],
	'rules'       => [],
	'timing'      => [
		'type' => 'immediately',
	],
	'actions'     => [
		[
			'name'    => 'send_email',
			'options' => [
				'to'            => '{{ customer.email }}',
				'subject'       => 'Your saved credit card is expiring soon!',
				'email_heading' => 'Your card is expiring soon! 💳',
				'preheader'     => '',
				'template'      => 'default',
				'email_content' => $email_content,
			],
		],
	],
];
