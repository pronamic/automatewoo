<?php

$email_content = <<<EMAIL
Hi {{ customer.first_name | fallback: 'there' }},

As our way of saying thank you for being one of our most valued customers, take XXX% off your next order!

Use the coupon code <strong>{{ customer.generate_coupon | template:'INSERT TEMPLATE COUPON NAME' }}</strong> to get your discount. The coupon is valid for the next 2 weeks, don't miss out.

<a href="{{ shop.url }}" class="automatewoo-button">Shop now!</a>

See you soon,
Your friends at {{ shop.title }}
EMAIL;

return [
	'title'       => 'Loyalty: Reward repeat customers',
	'description' => 'Trigger an email to reward customers who have made a certain number of orders on your store - include a custom custom offer.',
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
				'name'    => 'customer_order_count',
				'compare' => 'greater_than',
				'value'   => '3',
			],
			[
				'name'    => 'customer_total_spent',
				'compare' => 'greater_than',
				'value'   => '100',
			],
			[
				'name'    => 'customer_run_count',
				'compare' => 'is',
				'value'   => '0',
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
				'subject'       => 'Thank you from {{ shop.title }}!',
				'email_heading' => 'Thank you! ❤️',
				'preheader'     => '',
				'template'      => 'default',
				'email_content' => $email_content,
			],
		],
	],
];
