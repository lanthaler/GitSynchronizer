<?php

/*
 * (c) Markus Lanthaler <mail@markus-lanthaler.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ML\GitSynchronizer;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * GitSynchronizer
 *
 * @author Markus Lanthaler <mail@markus-lanthaler.com>
 */
class GitSynchronizer extends Application
{
    /**
     * Constructor
     *
     * Objects and parameters can be passed as argument to the constructor.
     *
     * @param array $values The parameters or objects.
     */
    public function __construct(array $values = array())
    {
        parent::__construct($values);

        $this->get('/', array($this, 'getStatus'));
        $this->post('/{token}', array($this, 'handleSynchronizationRequest'));

        $this->error(array($this, 'errorHandler'));
    }

    /**
     * Returns the current status
     *
     * Currently, it just returns a message saying how many repositories are
     * tracked.
     *
     * @return string The current status
     */
    public function getStatus()
    {
        $message = 'Tracking ';
        $numRepos = 0;

        if (isset($this['repositories']) &&
            (1 === ($numRepos = count($this['repositories'])))) {
            $message .= '1 repository.';
        } else {
            $message .= $numRepos . ' repositories.';
        }

        return $message;
    }

    /**
     * Handles a synchronization request
     *
     * @param Request         $request The request
     * @param string          $token   The token
     *
     * @return Response The response
     */
    public function handleSynchronizationRequest(Request $request, $token = null)
    {
        $data = json_decode($request->get('payload'), false);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception(400, 'The JSON data cannot be parsed');
        } elseif (false === isset($data->repository->url)) {
            throw new Exception(400, 'The JSON data does not contain the required information');
        }

        if (false === $this->synchronize($data->repository->url, $token)) {
            throw new Exception(500, 'Internal error', $data->repository->url);
        }

        return new Response('', 204);
    }

    /**
     * Handles a synchronization request
     *
     * @param string $reposity The URL of the repository to synchronize
     *
     * @return boolean Returns true on success, false on failure.
     */
    public function synchronize($repository, $token)
    {
        $this->log(LogLevel::INFO, 'Synchronization request for ' . $repository);

        if (false === isset($this['repositories'][$repository])) {
            throw new Exception(400, 'The repository has not been registered', $repository);
        }

        $repo = $this['repositories'][$repository];
        $reqToken = (is_object($repo) && isset($repo['token'])) ? $repo['token'] : $this['token'];
        $directory = (is_object($repo)) ? $repo['path'] : $repo;

        if (false === is_dir($directory)) {
            throw new Exception(500, 'Configuration error', $repository);
        }

        if ($token !== $reqToken) {
            throw new Exception(401, 'Wrong token, got "' . $token . '"', $repository);
        }

        $process = new Process("git pull");
        $process->setWorkingDirectory($directory);
        $process->setTimeout(120);
        $process->run();

        if ($process->isSuccessful()) {
            $this->log(
                LogLevel::NOTICE,
                'Successfully pulled ' . $repository,
                array('output' => $process->getOutput())
            );

            return true;
        } else {
            $this->log(
                LogLevel::ERROR,
                'Git pull failed.',
                array(
                    'repository' => $repository,
                    'directory' => $directory,
                    'output' => $process->getErrorOutput()
                )
            );

            return false;
        }
    }

    /**
     * Transforms uncaughted exceptions into HTTP responses
     *
     * @param \Exception $exception The URL of the repository to synchronize
     */
    public function errorHandler(\Exception $exception)
    {
        if ($exception instanceof Exception) {
            return new Response($exception->getPublicMessage());
        } else {
            return new Response('Whoops, looks like something went wrong.');
        }
    }

    /**
     * Log a message
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        if (isset($this['monolog'])) {
            $this['monolog']->log($level, $message, $context);
        }
    }
}
