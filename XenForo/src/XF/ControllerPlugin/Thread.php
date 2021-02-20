<?php

namespace XF\ControllerPlugin;

use XF\Mvc\Entity\Entity;
use XF\Mvc\FormAction;

class Thread extends AbstractPlugin
{
	public function getPostLink(\XF\Entity\Post $post)
	{
		$thread = $post->Thread;
		if (!$thread)
		{
			throw new \LogicException("Post has no thread");
		}

		$page = floor($post->position / $this->options()->messagesPerPage) + 1;
		$params = ['page' => $page];

		$typeHandler = $thread->TypeHandler;
		$isFirstPostAndPinned = $post->isFirstPost() && $typeHandler->isFirstPostPinned($thread);

		// If the default order is something else, we need to force date ordering as otherwise we can't
		// realistically find the post. Plus when requesting a specific post, we generally are assuming
		// the result will be date ordered. We can bypass this for the first post if it's pinned, as
		// we know we'll see it on page 1.
		if ($typeHandler->getDefaultPostListOrder($thread) != 'post_date' && !$isFirstPostAndPinned)
		{
			$params['order'] = 'post_date';
		}

		return $this->buildLink('threads', $thread, $params) . '#post-' . $post->post_id;
	}

	/**
	 * Provides a standard location to bulk preload data that may be associated with the posts to be displayed.
	 *
	 * @param iterable $posts
	 * @param \XF\Entity\Thread|null $thread Provided if the context is known to be loading posts from a single thread
	 * @param array $options Free form set of options/context to change behavior
	 */
	public function fetchExtraContentForPostsFullView($posts, \XF\Entity\Thread $thread = null, array $options = [])
	{
		/** @var \XF\Repository\Attachment $attachmentRepo */
		$attachmentRepo = $this->repository('XF:Attachment');
		$attachmentRepo->addAttachmentsToContent($posts, 'post');

		if (isset($options['skipRecrawl']))
		{
			$skipRecrawl = boolval($options['skipRecrawl']);
		}
		else
		{
			$skipRecrawl = boolval($this->request->getRobotName());
		}

		/** @var \XF\Repository\Unfurl $unfurlRepo */
		$unfurlRepo = $this->repository('XF:Unfurl');
		$unfurlRepo->addUnfurlsToContent($posts, $skipRecrawl);

		if ($thread)
		{
			$thread->TypeHandler->fetchExtraContentForPostsFullView($thread, $posts, $options);
		}
	}
}