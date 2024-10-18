<?php

$email_content = <<<EMAIL
Hi {{ customer.first_name | fallback: 'there' }},

It looks like you didn’t get to finish checking out on {{ shop.url }}, so we saved the items in your cart for you.

<strong>Here's what's waiting for you:</strong>

{{ cart.items }}

<a href="{{ cart.link }}" class="automatewoo-button">Click here to complete your order</a>.

See you soon,
Your friends at {{ shop.title }}
EMAIL;

return [
	'title'       => 'Abandoned cart (12 hours)',
	'description' => 'Trigger a follow up email to the customer in case they have abandoned their cart for more than 12 hours.',
	'type'        => 'automatic',
	'trigger'     => [
		'name'    => 'abandoned_cart_customer',
		'options' => [
			'user_pause_period' => '1',
		],
	],
	'rules'       => [],
	'timing'      => [
		'type'  => 'delayed',
		'delay' => [
			'unit'  => 'h',
			'value' => 12,
		],
	],
	'actions'     => [
		[
			'name'    => 'send_email',
			'options' => [
				'to'            => '{{ customer.email }}',
				'subject'       => "Still thinking it over, {{ customer.first_name | fallback: 'friend' }}?",
				'email_heading' => 'Your cart is waiting for you 🛒',
				'preheader'     => '',
				'template'      => 'default',
				'email_content' => $email_content,
			],
		],
	],
];
