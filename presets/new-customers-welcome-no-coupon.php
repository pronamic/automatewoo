<?php

$email_content = <<<EMAIL
Welcome {{ customer.first_name | fallback: 'friend' }},

Thanks for shopping with us at {{ shop.title }}! Be sure to visit again soon to check out these items that we think you'll love:

{{ order.related_products }}

See you soon,
Your friends at {{ shop.title }}
EMAIL;

return [
	'title'       => 'New Customers: Welcome email',
	'description' => 'Trigger an email when someone makes their first purchase on your store to welcome them as a customer.',
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
				'subject'       => 'Welcome to {{ shop.title }}!',
				'email_heading' => 'Welcome! 👋',
				'preheader'     => '',
				'template'      => 'default',
				'email_content' => $email_content,
			],
		],
	],
];
