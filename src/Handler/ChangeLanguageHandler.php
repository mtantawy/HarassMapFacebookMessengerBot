<?php
namespace HarassMapFbMessengerBot\Handler;

use HarassMapFbMessengerBot\User;
use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\Model\Message;
use Tgallice\FBMessenger\Model\Button\WebUrl;
use Tgallice\FBMessenger\Model\QuickReply\Text;
use Tgallice\FBMessenger\Callback\CallbackEvent;
use Tgallice\FBMessenger\Callback\MessageEvent;
use Tgallice\FBMessenger\Callback\PostbackEvent;
use Tgallice\FBMessenger\Model\Attachment\Template\Button;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Interop\Container\ContainerInterface;

class ChangeLanguageHandler implements Handler
{
    const FACEBOOK_SUPPORTED_LOCALES = [
        'en_US',
        'ca_ES',
        'cs_CZ',
        'cx_PH',
        'cy_GB',
        'da_DK',
        'de_DE',
        'eu_ES',
        'en_UD',
        'es_LA',
        'es_ES',
        'gn_PY',
        'fi_FI',
        'fr_FR',
        'gl_ES',
        'hu_HU',
        'it_IT',
        'ja_JP',
        'ko_KR',
        'nb_NO',
        'nn_NO',
        'nl_NL',
        'fy_NL',
        'pl_PL',
        'pt_BR',
        'pt_PT',
        'ro_RO',
        'ru_RU',
        'sk_SK',
        'sl_SI',
        'sv_SE',
        'th_TH',
        'tr_TR',
        'ku_TR',
        'zh_CN',
        'zh_HK',
        'zh_TW',
        'af_ZA',
        'sq_AL',
        'hy_AM',
        'az_AZ',
        'be_BY',
        'bn_IN',
        'bs_BA',
        'bg_BG',
        'hr_HR',
        'nl_BE',
        'en_GB',
        'et_EE',
        'fo_FO',
        'fr_CA',
        'ka_GE',
        'el_GR',
        'gu_IN',
        'hi_IN',
        'is_IS',
        'id_ID',
        'ga_IE',
        'jv_ID',
        'kn_IN',
        'kk_KZ',
        'lv_LV',
        'lt_LT',
        'mk_MK',
        'mg_MG',
        'ms_MY',
        'mt_MT',
        'mr_IN',
        'mn_MN',
        'ne_NP',
        'pa_IN',
        'sr_RS',
        'so_SO',
        'sw_KE',
        'tl_PH',
        'ta_IN',
        'te_IN',
        'ml_IN',
        'uk_UA',
        'uz_UZ',
        'vi_VN',
        'km_KH',
        'tg_TJ',
        'ar_AR',
        'he_IL',
        'ur_PK',
        'fa_IR',
        'ps_AF',
        'my_MM',
        'qz_MM',
        'or_IN',
        'si_LK',
        'rw_RW',
        'cb_IQ',
        'ha_NG',
        'ja_KS',
        'br_FR',
        'tz_MA',
        'co_FR',
        'as_IN',
        'ff_NG',
        'sc_IT',
        'sz_PL',
    ];

    const LOCALE_DEFAULT = 'ar_AR';

    private $messenger;

    private $event;

    private $user;

    private $dbConnection;

    protected $container;

    public function __construct(
        ContainerInterface $container,
        CallbackEvent $event,
        User $user
    ) {
        $this->container = $container;
        $this->event = $event;
        $this->user = $user;
        $this->messenger = $this->container->messenger;
        $this->dbConnection = $this->container->dbConnection;
    }

    public function handle()
    {
        if (($this->event instanceof MessageEvent
            && $this->event->getQuickReplyPayload() === 'CHANGE_LANGUAGE')
            || ($this->event instanceof PostbackEvent
            && $this->event->getPostbackPayload() === 'CHANGE_LANGUAGE')) {
            $this->displayAvailableLanguages();
        } elseif ($this->event instanceof MessageEvent
            && 0 === mb_strpos($this->event->getQuickReplyPayload(), 'CHANGE_LANGUAGE_')) {
            $this->changeLanguage();
        }
    }

    private function displayAvailableLanguages()
    {
        $message = new Message(
            $this->container->translationService->getLocalizedString(
                'choose_language',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            )
        );
        $message->setQuickReplies([
            new Text(
                $this->container->translationService->getLocalizedString(
                    'arabic',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'CHANGE_LANGUAGE_ar_AR'
            ),
            new Text(
                $this->container->translationService->getLocalizedString(
                    'english',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'CHANGE_LANGUAGE_en_US'
            ),
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }

    public function changeLanguage()
    {
        $preferredLanguage = mb_substr(
            $this->event->getQuickReplyPayload(),
            mb_strlen('CHANGE_LANGUAGE_')
        );

        $preferredLanguage = in_array($preferredLanguage, self::FACEBOOK_SUPPORTED_LOCALES) ? $preferredLanguage : self::LOCALE_DEFAULT;

        $changeStatus = (bool) $this->container->userService->updateUserPreferredLanguage(
            $this->user,
            $preferredLanguage
        );

        if ($changeStatus) {
            $this->user = $this->container->userService->getById($this->user->getId());

            $message = new Message(
                $this->container->translationService->getLocalizedString(
                    'language_changed',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                )
            );
            $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
        }

        $getStartedHandler = new GetStartedHandler(
            $this->container,
            $this->event,
            $this->user
        );

        $getStartedHandler->handle();
    }
}
