<?php
/**
 * app/controllers/EntityController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Elabftw\Exceptions\IllegalActionException;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Deal with things common to experiments and items like tags, uploads, quicksave and lock
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';


try {
    // id of the item (experiment or database item)
    $id = 1;

    if ($Request->request->has('id')) {
        $id = $Request->request->get('id');
    } elseif ($Request->query->has('id')) {
        $id = $Request->query->get('id');
    }

    if ($Request->request->get('type') === 'experiments' ||
        $Request->query->get('type') === 'experiments') {
        $Entity = new Experiments($App->Users, $id);
    } elseif ($Request->request->get('type') === 'experiments_tpl') {
        $Entity = new Templates($App->Users, $id);
    } else {
        $Entity = new Database($App->Users, $id);
    }

    $Response = new RedirectResponse("../../" . $Entity->page . ".php?mode=edit&id=" . $Entity->id);

    /**
     * GET REQUESTS
     *
     */

    // DUPLICATE
    if ($Request->query->has('duplicate')) {
        $Entity->canOrExplode('read');
        $id = $Entity->duplicate();
    }

    /**
     * POST REQUESTS
     *
     */

    // UPDATE
    if ($Request->request->has('update')) {
        $Entity->update(
            $Request->request->get('title'),
            $Request->request->get('date'),
            $Request->request->get('body')
        );
        // redirect to view mode (Save and go back button)
        $Response = new RedirectResponse("../../" . $Entity->page . ".php?mode=view&id=" . $Entity->id);
    }

    // REPLACE UPLOAD
    if ($Request->request->has('replace')) {
        $Entity->Uploads->replace($Request);
    }

} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->__toString())));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid') ?? 'anon'), array('exception' => $e->__toString())));
    $App->Session->getFlashBag()->add('ko', Tools::error());

} finally {
    $Response->send();
}
