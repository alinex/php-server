<?php
/**
 * @file
 * Doctrine cli interface initialization.
 *
 * This file will set up the HelperSet for doctrine's command line tool to work. Thjerefore the
 * entity manager is stored in variable \c $em and a helper set in \c $helperSet.
 *
 * @copyright \ref Copyright (c) 2009 - 2011, Alexander Schilling
 * @license All Alinex code is released under the GNU General Public \ref License.
 * @author Alexander Schilling <info@alinex.de>
 * @see http://alinex.de Alinex Project
 */

include 'source/bootstrap.php';

// get entity manager working with annotations
$em = Alinex\DB\EntityManager::getInstance();

// initialize the helper set for doctrine cli
$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
));
