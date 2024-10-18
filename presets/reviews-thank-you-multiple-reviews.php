<?php

$email_content = <<<EMAIL
Hi {{ customer.first_name | fallback: 'there' }},

Thanks for leaving your review on {{ shop.title }}. We love hearing your feedback, so we'd like to say a special thank you.

Use the coupon code <strong>{{ customer.generate_coupon | template: 'INSERT TEMPLATE COUPON NAME' }}</strong> to enjoy XXX% off your next purchase!

<a href="{{ shop.url }}" class="automatewoo-button">Shop now!</a>

See you soon,
Your friends at {{ shop.title }}
EMAIL;

return [
	'title'       => 'Reviews: Reward customers for multiple reviews (with coupon)',
	'description' => 'Trigger an email to send to customers who leave over a pre-determined number of reviews on your products - include a personalized coupon in the email. The workflow will only send the reward once.',
	'type'        => 'automatic',
	'trigger'     => [
		'name' => 'review_posted',
	],
	'rules'       => [
		[
			[
				'name'    => 'customer_review_count',
				'compare' => 'greater_than',
				'value'   => '5',
			],
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
