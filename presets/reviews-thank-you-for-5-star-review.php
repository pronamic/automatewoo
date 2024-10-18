<?php

$email_content = <<<EMAIL
Hi {{ customer.first_name | fallback: 'there' }},

Thanks for leaving a review on {{ shop.title }}. We love hearing your feedback, so we'd like to say a special thank you.

Use the coupon code <strong>{{ customer.generate_coupon | template: 'INSERT TEMPLATE COUPON NAME' }}</strong> to enjoy XXX% off your next purchase!

<a href="{{ shop.url }}" class="automatewoo-button">Shop now!</a>

See you soon,
Your friends at {{ shop.title }}
EMAIL;

return [
	'title'       => 'Reviews: Thank-you for first 5-star review (with coupon)',
	'description' => 'Trigger a special thank you email to customers who leave a 5 star review on their purchased products  - include a personalized coupon in the email. The workflow will only send the email once.',
	'type'        => 'automatic',
	'trigger'     => [
		'name' => 'review_posted',
	],
	'rules'       => [
		[
			[
				'name'    => 'review_rating',
				'compare' => 'is',
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
