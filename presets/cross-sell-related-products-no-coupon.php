<?php

$email_content = <<<EMAIL
Hi {{ customer.first_name | fallback: 'there' }},

Thanks again for your order! We've found some other items that we think you'll love:

{{ order.related_products }}

See you soon,
Your friends at {{ shop.title }}
EMAIL;

return [
	'title'       => 'Cross sell: Related products',
	'description' => 'Trigger an email to encourage a repeat purchase from customers by showing them complementary products or services depending on what they\'ve purchased.',
	'type'        => 'automatic',
	'trigger'     => [
		'name'    => 'order_completed',
		'options' => [
			'validate_order_status_before_queued_run' => '1',
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
				'subject'       => "Thanks for your order {{ customer.first_name | fallback: '' }}",
				'email_heading' => 'Check these out! 👀',
				'preheader'     => '',
				'template'      => 'default',
				'email_content' => $email_content,
			],
		],
	],
];
