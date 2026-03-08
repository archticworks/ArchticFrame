// jQuery(function ($) {
// 	$(document).on('click', '.archticframe-create-archive', function (e) {
// 		e.preventDefault();

// 		const $button = $(this);
// 		const postType = $button.data('post-type');

// 		if (!postType || typeof archticframeAdmin === 'undefined') {
// 			return;
// 		}

// 		$button.prop('disabled', true).text('Creating...');

// 		$.post(archticframeAdmin.ajaxUrl, {
// 			action: 'archticframe_create_archive_post',
// 			nonce: archticframeAdmin.nonce,
// 			post_type: postType
// 		})
// 			.done(function (response) {
// 				if (!response || !response.success || !response.data) {
// 					alert('Could not create archive post.');
// 					return;
// 				}

// 				const data = response.data;
// 				const $select = $('#archticframe-' + postType);

// 				if ($select.length) {
// 					const exists = $select.find('option[value="' + data.id + '"]').length > 0;

// 					if (!exists) {
// 						$select.append(
// 							$('<option>', {
// 								value: data.id,
// 								text: data.title
// 							})
// 						);
// 					}

// 					$select.val(String(data.id));
// 				}
// 			})
// 			.fail(function () {
// 				alert('Could not create archive post.');
// 			})
// 			.always(function () {
// 				$button.prop('disabled', false).text('Create archive post');
// 			});
// 	});
// });