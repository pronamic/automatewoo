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
	'title'       => 'Loyalty: Reward high-spending customers (with coupon)',
	'description' => 'Trigger an email to reward customers when their total spend with your store reaches a predetermined amount - include a personalized coupon in the email.',
	'type'        => 'automatic',
	'trigger'     => [
		'name'    => 'users_total_spend',
		'options' => [
			'total_spend' => '500',
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
				'subject'       => 'A gift for {{ customer.first_name | fallback: \'our favorite customer\' }}!',
				'email_heading' => 'Thank you! ❤️',
				'preheader'     => '',
				'template'      => 'default',
				'email_content' => $email_content,
			],
		],
	],
];
