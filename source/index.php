<?php
/**
 * @file
 * RPC Server start.
 *
 * This file will only invoke the rpc server to handle incomming requests. The
 * further processing of each request will be
 * handled completely by the RPC server. Therefore the incomming data has to
 * correspond to at least one of the supported
 * RPC server schemas. The result will also adopt the requesting format.
 *
 * On any fatal errors like misconfigured system, the RPC will not start and
 * won't send any responses but log
 * the reasons as fatal message in category 'core'.
 *
 * @todo rewrite code
 * @todo document overview of rpc possibilities
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de
 */

include_once 'bootstrap.php';

echo tr("Test entry");

// try to run a rpc server
if (!\core\rpc\RpcServer::run())
    // forward to base page
    header("Location: ../");
