<?php

$email_content = <<<EMAIL
Hi {{ customer.first_name | fallback: 'there' }},

Thanks for shopping with us at {{ shop.title }}, we're thrilled to have you as a customer! Be sure to visit again soon to check out these other great items:

{{ order.related_products }}

See you soon,
Your friends at {{ shop.title }}
EMAIL;

return [
	'title'       => 'Cross sell: Target first-time customers',
	'description' => 'Trigger an email to encourage a repeat purchase from first-time customers by showing them complementary products or services depending on what they\'ve already purchased.',
	'type'        => 'automatic',
	'trigger'     => [
		'name'    => 'order_completed',
		'options' => [
			'validate_order_status_before_queued_run' => '1',
		],
	],
	'rules'       => [
		[
			[
				'name'    => 'order_is_customers_first',
				'compare' => '',
				'value'   => 'yes',
			],
		],
	],
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
				'subject'       => "Welcome, {{ customer.first_name | fallback: 'friend' }}!",
				'email_heading' => 'Welcome! 👋🏻',
				'preheader'     => '',
				'template'      => 'default',
				'email_content' => $email_content,
			],
		],
	],
];
