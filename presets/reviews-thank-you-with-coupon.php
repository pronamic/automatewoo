<?php

$email_content = <<<EMAIL
Hi {{ customer.first_name | fallback: 'there' }},

Thanks for leaving a review of your purchase. We love hearing your feedback, so we'd like to say a special thank you.

Enjoy XXX% off your next purchase with the coupon code <strong>{{ customer.generate_coupon | template: 'INSERT TEMPLATE COUPON NAME' }}</strong>!

<a href="{{ shop.url }}" class="automatewoo-button">Shop now!</a>

See you soon,
Your friends at {{ shop.title }}
EMAIL;

return [
	'title'       => 'Reviews: Reward customers for a review (with coupon)',
	'description' => 'Trigger an email to customers who leave a review on your products - include a personalized coupon in the email. The workflow will only send the reward once.',
	'type'        => 'automatic',
	'trigger'     => [
		'name' => 'review_posted',
	],
	'rules'       => [
		[
			[
				'name'    => 'customer_run_count',
				'compare' => 'is',
				'value'   => '0',
			],
		],
	],
	'timing'      => [
		'type' => 'immediately',
	],
	'actions'     => [
		[
			'name'    => 'send_email',
			'options' => [
				'to'            => '{{ customer.email }}',
				'subject'       => 'Thanks for your feedback at {{ shop.title }}!',
				'email_heading' => 'Thank you! ❤️',
				'preheader'     => '',
				'template'      => 'default',
				'email_content' => $email_content,
			],
		],
	],
];
