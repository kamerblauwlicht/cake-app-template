<?php
declare(strict_types = 1);
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Network\Response;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    use \AuthActions\Lib\AuthActionsTrait;
    use \FrontendBridge\Lib\FrontendBridgeTrait;

    /**
     * Load components
     *
     * @var array
     */
    public $components = [
        'Flash',
        'FrontendBridge.FrontendBridge',
        'ListFilter.ListFilter',
        'Cookie',
        'AuthActions.AuthUtils',
        'CakeApiBaselayer.Api'
    ];

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->loadComponent('RequestHandler');
        $this->loadComponent('Auth', [
            'authenticate' => [
                'Form' => [
                    'fields' => ['username' => 'email'],
                    'repository' => 'Users',
                    'finder' => 'auth'
                ]
            ],
            'authorize' => ['Controller'],
            'loginAction' => ['plugin' => false, 'controller' => 'Login', 'action' => 'login'],
            'loginRedirect' => ['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index'],
            'logoutRedirect' => ['plugin' => false, 'controller' => 'Home', 'action' => 'index'],
            'authError' => __('auth.not_allowed'),
            'flash' => [
                'params' => [
                    'class' => 'alert alert-warning'
                ]
            ],
        ]);
        /*
         * Enable the following components for recommended CakePHP security settings.
         * see http://book.cakephp.org/3.0/en/controllers/components/security.html
         */
        //$this->loadComponent('Security');
        //$this->loadComponent('Csrf');

        parent::initialize();
    }

    /**
     * Before render callback.
     *
     * @param \Cake\Event\Event $event The beforeRender event.
     * @return \Cake\Network\Response|null|void
     */
    public function beforeRender(Event $event)
    {
        if (!array_key_exists('_serialize', $this->viewVars) &&
            in_array($this->response->type(), ['application/json', 'application/xml'])
        ) {
            $this->set('_serialize', true);
        }
    }

    /**
     * Called before the controller action. You can use this method to configure and customize components
     * or perform logic that needs to happen before each controller action.
     *
     * @param \Cake\Event\Event $event An Event instance
     * @return \Cake\Network\Response|null|void
     */
    public function beforeFilter(\Cake\Event\Event $event)
    {
        $this->initAuthActions();
        $this->loadModel('Users');
        $this->Auth->eventManager()->attach([$this->Users, 'resetLoginRetriesListener'], 'Auth.afterIdentify');

        if (!$this->AuthUtils->loggedIn() && $userId = $this->AuthUtils->checkRememberMeCookie()) {
            $this->loadModel('Users');
            $user = $this->Users->get($userId)->toArray();
            $this->Auth->setUser($user);
        }

        if (!$this->Auth->user()) {
            $this->Auth->config('authError', false);
        }

        parent::beforeFilter($event);
    }

    /**
     * Instantiates the correct view class, hands it its data, and uses it to render the view output.
     *
     * @param mixed $view View to use for rendering
     * @param mixed $layout Layout to use
     * @return \Cake\Network\Response A response object containing the rendered view.
     */
    public function render($view = null, $layout = null): Response
    {
        if ($this->_isJsonActionRequest()) {
            return $this->renderJsonAction($view, $layout);
        }

        return parent::render($view, $layout);
    }
}
