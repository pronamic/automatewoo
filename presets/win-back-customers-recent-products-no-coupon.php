<?php

$email_content = <<<EMAIL
Hi {{ customer.first_name | fallback: 'there' }},

Have you seen these great items we just added?

{{ shop.products | type: 'recent' }}

See you soon,
Your friends at {{ shop.title }}
EMAIL;

return [
	'title'       => 'Win back: Promote recent products',
	'description' => 'Trigger an email to encourage customers back to your store by showing them your recent products.',
	'type'        => 'automatic',
	'trigger'     => [
		'name'    => 'user_absent',
		'options' => [
			'time'           => [
				'22',
				'56',
			],
			'enable_repeats' => 0,
		],
	],
	'rules'       => [],
	'timing'      => [
		'type'  => 'delayed',
		'delay' => [
			'unit'  => 'h',
			'value' => 1,
		],
	],
	'actions'     => [
		[
			'name'    => 'send_email',
			'options' => [
				'to'            => '{{ customer.email }}',
				'subject'       => "Don't miss the latest products from {{ shop.title }}!",
				'email_heading' => 'New products are here!',
				'preheader'     => '',
				'template'      => 'default',
				'email_content' => $email_content,
			],
		],
	],
];
