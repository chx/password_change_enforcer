<?php

namespace Drupal\password_change_enforcer\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PasswordChangeEnforcer implements EventSubscriberInterface  {

  /**
   * The current route name.
   *
   * @var string
   */
  protected $routeName;

  /**
   * The current user id.
   *
   * @var int
   */
  protected $accountId;

  public function __construct(RouteMatchInterface $route_match, AccountInterface $account) {
    // Maybe it is not the best to call methods here but this service will
    // always run and both are needed.
    $this->routeName = $route_match->getRouteName();
    $this->accountId = $account->id();
  }

  public function enforcer(GetResponseEvent $event) {
    // This is set in \Drupal\user\AccountForm::form() and because it does not
    // use the injected @session service, we can not use it either as the
    // service uses the top level '_sf2_attributes'.
    $session_key = "pass_reset_$this->accountId";
    if ($this->routeName != 'entity.user.edit_form' && isset($_SESSION[$session_key])) {
      drupal_set_message(t('You must change your password.'));
      // Copied from \Drupal\user\Controller\UserController::resetPassLogin()
      $url = Url::fromRoute('entity.user.edit_form',
        ['user' => $this->accountId],
        [
          'query' => ['pass-reset-token' => $_SESSION[$session_key]],
          'absolute' => TRUE,
        ]
      )->toString();
      $event->setResponse(new RedirectResponse($url));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['enforcer', 31];
    return $events;
  }

}
