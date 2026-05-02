<?php

/*
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018-2022 by the Releases Development Team.
 -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Releases\Tests;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Releases\Deploytask;
use GlpiPlugin\Releases\Release;
use GlpiPlugin\Releases\Review;
use GlpiPlugin\Releases\Risk;
use GlpiPlugin\Releases\Rollback;
use GlpiPlugin\Releases\Test as ReleaseTest;
use ITILFollowup;

/**
 * Tests de création d'une release et de ses sous-éléments.
 *
 * Prérequis : le plugin releases doit être installé dans la base de test.
 * Lancer depuis la racine GLPI :
 *   vendor/bin/phpunit -c marketplace/releases/phpunit.xml
 */
class ReleaseObjectTest extends DbTestCase
{
    private int $entities_id;

    protected function setUp(): void
    {
        parent::setUp();
        // Super-admin pour disposer de tous les droits (y compris droits plugin)
        $this->login('glpi', 'glpi');
        $this->entities_id = getItemByTypeName('Entity', '_test_root_entity', true);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createRelease(array $extra = []): Release
    {
        $release = new Release();
        $id      = $release->add(array_merge([
            'name'          => 'Release de test',
            'entities_id'   => $this->entities_id,
            '_disablenotif' => true,
        ], $extra));
        $this->assertGreaterThan(0, $id, 'La release doit être créée avec un ID valide.');
        return $release;
    }

    // -------------------------------------------------------------------------
    // Création de release
    // -------------------------------------------------------------------------

    public function testCreateReleaseMinimale(): void
    {
        $release = new Release();
        $id      = $release->add([
            'name'          => 'Release minimale',
            'entities_id'   => $this->entities_id,
            '_disablenotif' => true,
        ]);
        $this->checkInput($release, $id, [
            'name'        => 'Release minimale',
            'entities_id' => $this->entities_id,
            'status'      => Release::NEWRELEASE,
        ]);
    }

    public function testStatusPasseAReleasedefinitionAvecContenu(): void
    {
        $release = new Release();
        $id      = $release->add([
            'name'          => 'Release avec contenu',
            'entities_id'   => $this->entities_id,
            'content'       => '<p>Description de la release</p>',
            '_disablenotif' => true,
        ]);
        $this->checkInput($release, $id, [
            'status' => Release::RELEASEDEFINITION,
        ]);
    }

    public function testStatusPasseADatedefinitionAvecDates(): void
    {
        $release = new Release();
        $id      = $release->add([
            'name'               => 'Release avec dates',
            'entities_id'        => $this->entities_id,
            'date_preproduction' => '2026-06-01 10:00:00',
            'date_production'    => '2026-06-15 10:00:00',
            '_disablenotif'      => true,
        ]);
        $this->checkInput($release, $id, [
            'status'             => Release::DATEDEFINITION,
            'date_preproduction' => '2026-06-01 10:00:00',
            'date_production'    => '2026-06-15 10:00:00',
        ]);
    }

    // -------------------------------------------------------------------------
    // Suivi (ITILFollowup)
    // -------------------------------------------------------------------------

    public function testAjouterSuivi(): void
    {
        $release  = $this->createRelease(['content' => '<p>Contenu de test</p>']);
        $followup = new ITILFollowup();
        $id       = $followup->add([
            'items_id'      => $release->getID(),
            'itemtype'      => Release::class,
            'content'       => '<p>Mise à jour intermédiaire de la release</p>',
            '_disablenotif' => true,
        ]);
        $this->checkInput($followup, $id, [
            'items_id' => $release->getID(),
            'itemtype' => Release::class,
        ]);
    }

    // -------------------------------------------------------------------------
    // Risque
    // -------------------------------------------------------------------------

    public function testAjouterRisque(): void
    {
        $release = $this->createRelease();
        $risk    = new Risk();
        $id      = $risk->add([
            'name'          => 'Risque de coupure réseau',
            'items_id'      => $release->getID(),
            'content'       => 'La coupure réseau peut bloquer le déploiement.',
            '_disablenotif' => true,
        ]);
        $this->checkInput($risk, $id, [
            'name'                        => 'Risque de coupure réseau',
            'plugin_releases_releases_id' => $release->getID(),
            'entities_id'                 => $this->entities_id,
            'state'                       => Risk::TODO,
        ]);
    }

    public function testChangerEtatRisque(): void
    {
        $release = $this->createRelease();
        $risk    = new Risk();
        $id      = $risk->add([
            'name'          => 'Risque à résoudre',
            'items_id'      => $release->getID(),
            '_disablenotif' => true,
        ]);
        $this->assertGreaterThan(0, $id);

        $updated = $risk->update(['id' => $id, 'state' => Risk::DONE]);
        $this->assertTrue($updated);
        $risk->getFromDB($id);
        $this->assertEquals(Risk::DONE, (int) $risk->fields['state']);
    }

    // -------------------------------------------------------------------------
    // Tâche de déploiement
    // -------------------------------------------------------------------------

    public function testAjouterTacheDeploiementSansDate(): void
    {
        $release = $this->createRelease();
        $task    = new Deploytask();
        $id      = $task->add([
            'name'          => 'Déploiement du package applicatif',
            'items_id'      => $release->getID(),
            'content'       => 'Copier les artefacts et redémarrer le service.',
            '_disablenotif' => true,
        ]);
        $this->checkInput($task, $id, [
            'name'                        => 'Déploiement du package applicatif',
            'plugin_releases_releases_id' => $release->getID(),
            'entities_id'                 => $this->entities_id,
            'state'                       => Deploytask::TODO,
        ]);
    }

    public function testAjouterTacheDeploiementAvecPlage(): void
    {
        $release = $this->createRelease();
        $task    = new Deploytask();
        $id      = $task->add([
            'name'     => 'Maintenance planifiée',
            'items_id' => $release->getID(),
            'plan'     => [
                'begin' => '2026-06-01 22:00:00',
                'end'   => '2026-06-02 01:00:00',
            ],
            '_disablenotif' => true,
        ]);
        $this->checkInput($task, $id, [
            'plugin_releases_releases_id' => $release->getID(),
            'begin'                       => '2026-06-01 22:00:00',
            'end'                         => '2026-06-02 01:00:00',
            'actiontime'                  => 10800, // 3 heures en secondes
        ]);
    }

    // -------------------------------------------------------------------------
    // Test de release
    // -------------------------------------------------------------------------

    public function testAjouterTestDeRelease(): void
    {
        $release     = $this->createRelease();
        $releaseTest = new ReleaseTest();
        $id          = $releaseTest->add([
            'name'          => 'Test de non-régression login',
            'items_id'      => $release->getID(),
            'content'       => 'Vérifier que la page de login répond en moins de 2s.',
            '_disablenotif' => true,
        ]);
        $this->checkInput($releaseTest, $id, [
            'name'                        => 'Test de non-régression login',
            'plugin_releases_releases_id' => $release->getID(),
            'entities_id'                 => $this->entities_id,
            'state'                       => ReleaseTest::TODO,
        ]);
    }

    public function testEchecTestDeRelease(): void
    {
        $release     = $this->createRelease();
        $releaseTest = new ReleaseTest();
        $id          = $releaseTest->add([
            'name'          => 'Test de performance',
            'items_id'      => $release->getID(),
            '_disablenotif' => true,
        ]);
        $this->assertGreaterThan(0, $id);

        $releaseTest->update(['id' => $id, 'state' => ReleaseTest::FAIL]);
        $releaseTest->getFromDB($id);
        $this->assertEquals(ReleaseTest::FAIL, (int) $releaseTest->fields['state']);
    }

    // -------------------------------------------------------------------------
    // Rollback
    // -------------------------------------------------------------------------

    public function testAjouterRollback(): void
    {
        $release  = $this->createRelease();
        $rollback = new Rollback();
        $id       = $rollback->add([
            'name'          => 'Rollback vers la version 1.2',
            'items_id'      => $release->getID(),
            'content'       => 'Restaurer la sauvegarde DB et redéployer v1.2.',
            '_disablenotif' => true,
        ]);
        $this->checkInput($rollback, $id, [
            'name'                        => 'Rollback vers la version 1.2',
            'plugin_releases_releases_id' => $release->getID(),
            'entities_id'                 => $this->entities_id,
            'state'                       => Rollback::TODO,
        ]);
    }

    public function testRollbackCompteCorrectemment(): void
    {
        $release  = $this->createRelease();
        $rollback = new Rollback();

        $this->assertEquals(0, Rollback::countForItem($release));

        $rollback->add([
            'name'          => 'Rollback #1',
            'items_id'      => $release->getID(),
            '_disablenotif' => true,
        ]);
        $rollback->add([
            'name'          => 'Rollback #2',
            'items_id'      => $release->getID(),
            '_disablenotif' => true,
        ]);

        $this->assertEquals(2, Rollback::countForItem($release));
    }

    // -------------------------------------------------------------------------
    // Revue (Review)
    // -------------------------------------------------------------------------

    public function testAjouterRevue(): void
    {
        $release = $this->createRelease(['content' => '<p>Release à réviser</p>']);
        $review  = new Review();
        $id      = $review->add([
            'plugin_releases_releases_id' => $release->getID(),
            'name'                        => 'Revue de la release de test',
            'conforming_realization'      => 1,
            'incident'                    => 0,
            '_disablenotif'               => true,
        ]);
        $this->checkInput($review, $id, [
            'plugin_releases_releases_id' => $release->getID(),
            'conforming_realization'      => 1,
            'incident'                    => 0,
        ]);
    }
}
