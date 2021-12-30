<?php
/*
 * @package   plg_content_flarticlenotify
 * @version   1.0.0
 * @author    Dmitriy Vasyukov - https://fictionlabs.ru
 * @copyright Copyright (c) 2021 Fictionlabs. All rights reserved.
 * @license   GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link      https://fictionlabs.ru/
 */

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class plgContentFLArticleNotify extends CMSPlugin
{
    /**
	 * Affects constructor behavior.
	 *
	 * @var  boolean
	 *
	 * @since   1.0.0
	 */
	protected $autoloadLanguage = true;

    /**
     * After save content method
     *
     * @param   string   $context  The context of the content passed to the plugin (added in 1.6)
     * @param   object   $article  A JTableContent object
     * @param   boolean  $isNew    If the content is just about to be created
     *
     * @return  boolean   true if function not enabled, is in frontend or is new. Else true or
     *                    false depending on success of save function.
     *
     * @since   1.6
     */
    public function onContentAfterSave($context, $article, $isNew)
    {
        // Check we are handling the frontend edit form.
        if ($context !== 'com_content.form')
        {
            return true;
        }

        // Check this is a new article.
        if (!$isNew)
        {
            return true;
        }

        // Check plugin params
        if (empty($this->params->get('email')))
		{
            return true;
        }

        return $this->sendMail($article);
    }


    /**
     * Send email to config email
     * @param object   $article  A JTableContent object
     * @since version 1.0.0
     */
    public function sendMail($article)
    {
        $config = Factory::getConfig();
	    $mailer = Factory::getMailer();

	    // Params
	    $emailReply     = $config->get('mailfrom');
        $emailText      = $this->params->get('email_text');
        $subject        = $this->params->get('email_subject') ? $this->params->get('email_subject') : Text::sprintf('PLG_CONTENT_FLARTICLENOTIFY_EMAIL_SUBJECT', $config->get('fromname'));
        $email          = $this->params->get(('email')) ? array_map('trim', explode(',', $this->params->get(('email')))) : $config->get('mailfrom');
        $emailEditLink  = Uri::root().'administrator/index.php?option=com_content&task=article.edit&id='.$article->id;

        // Email variables
        $emailHeadText      = Text::_('PLG_CONTENT_FLARTICLENOTIFY_TMPL_HEAD_TEXT');
        $emailIdText        = Text::_('PLG_CONTENT_FLARTICLENOTIFY_TMPL_ID');
        $emailTitleText     = Text::_('PLG_CONTENT_FLARTICLENOTIFY_TMPL_TITLE');
        $emailContentText   = Text::_('PLG_CONTENT_FLARTICLENOTIFY_TMPL_CONTENT');
        $emailEditText      = Text::_('PLG_CONTENT_FLARTICLENOTIFY_TMPL_EDIT_LINK');
        $emailFooterText    = Text::_('PLG_CONTENT_FLARTICLENOTIFY_TMPL_FOOTER_TEXT');
        $emailSiteUrl       = Uri::root();

	    // Get sender
        $sender = array(
			$config->get('mailfrom'),
			$config->get('fromname')
		);

	    // Get body
        $path = PluginHelper::getLayoutPath('content', 'flarticlenotify');

		ob_start();
		include $path;
		$body = ob_get_clean();

	    $mailer->setSender($sender);
	    $mailer->setSubject($subject);
	    $mailer->isHtml(true);
	    $mailer->Encoding = 'base64';
	    $mailer->addReplyTo($emailReply);
	    $mailer->addRecipient($email);
	    $mailer->setBody($body);

	    $send = $mailer->Send();

	    if ($send !== true) {
            $message = $send->getMessage();
            $this->log('plg_content_flarticlenotify', 'Mail error', $message);
        }

        return true;
    }

    /**
     * Log function
     *
	 * @param string $name Name of the log file
	 * @param string $value Value of the message
	 * @param string|array $message Massage string or message array
	 * @return true
	 * @since 1.0.0
	 */
	protected function log($name, $value, $message) {
		if (empty($message)) {
			return true;
		}

		if (is_array($message)) {
			$message = print_r($message, true);
		}

		// Add logger
        Log::addLogger(
	        array(
		        'text_file' => $name.'.log.php'
	        ),
	        JLog::ALL,
	        array($name)
        );

        // Add message to log
        Log::add($value.': '.$message, JLog::INFO, $name);

        return true;
	}
}
