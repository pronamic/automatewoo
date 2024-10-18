<?php

$email_content = <<<EMAIL
Hi {{ customer.first_name | fallback: 'there' }},

Thanks for shopping with {{ shop.title }}! Would you like to tell us what you think of your purchase? We'd love to hear your feedback.

{{ order.items | template: 'review-rows' }}

See you soon,
Your friends at {{ shop.title }}
EMAIL;

return [
	'title'       => 'Reviews: Remind customers to leave a review',
	'description' => 'Trigger an email post-purchase to send to customers to help solicit product reviews which can be used to help build your SEO and drive more traffic to your store.',
	'type'        => 'automatic',
	'trigger'     => [
		'name'    => 'order_completed',
		'options' => [
			'validate_order_status_before_queued_run' => '1',
		],
	],
	'timing'      => [
		'type'  => 'delayed',
		'delay' => [
			'unit'  => 'w',
			'value' => 2,
		],
	],
	'actions'     => [
		[
			'name'    => 'send_email',
			'options' => [
				'to'            => '{{ customer.email }}',
				'subject'       => 'What do you think?',
				'email_heading' => 'Share your thoughts! 📣',
				'preheader'     => '',
				'template'      => 'default',
				'email_content' => $email_content,
			],
		],
	],
];
