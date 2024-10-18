<?php

$email_content = <<<EMAIL
Hi {{ customer.first_name | fallback: 'there' }},

Thanks for shopping with us at {{ shop.title }}, we're thrilled to have you as a customer!

Be sure to visit again soon to check out these other great items, and use the coupon code <strong>{{ customer.generate_coupon | template:'INSERT TEMPLATE COUPON NAME' }} to save XXX% on your next purchase!

{{ order.related_products }}

See you soon,
Your friends at {{ shop.title }}
EMAIL;

return [
	'title'       => 'Cross sell: Target first-time customers (with coupon)',
	'description' => 'Trigger an email to encourage a repeat purchase from first-time customers by showing them complementary products or services depending on what they\'ve purchased - include a personalized coupon in the email.',
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
				'subject'       => "A welcome gift for {{ customer.first_name | fallback: 'you' }}!",
				'email_heading' => 'Welcome! 👋🏻',
				'preheader'     => '',
				'template'      => 'default',
				'email_content' => $email_content,
			],
		],
	],
];
