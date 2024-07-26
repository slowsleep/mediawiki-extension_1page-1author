<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Logger\LoggerFactory;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * OnlyAuthorCanEditPage
 *
 *
 * @file
 * @ingroup Extensions
 *
 * @license GPL-2.0-or-later
 */
class OnlyAuthorCanEditPage {

    private static function getRecordPageAuthor($pageId, $userId) {
        $dbProvider = MediaWikiServices::getInstance()->getConnectionProvider();
        $dbr = $dbProvider->getReplicaDatabase();
        $res = $dbr->newSelectQueryBuilder()
            ->select( [ 'id' ] )
            ->from( 'page_authors' )
            ->where( [
                'page_id' => $pageId,
                'author_id' => $userId,
            ] )
            ->caller( __METHOD__ )->fetchRow();

        return $res;
    }

    /**
	 * @param Title $title
	 * @param User $user
	 * @param string $action
	 * @param array|string|MessageSpecifier &$result
	 *
	 * @return bool
	 */
    public static function onGetUserPermissionsErrors( $title, $user, $action, &$result ) {
        if ( !( $action == 'edit' || $action == 'create' || $action == 'move' ) ) {
			$result = null;
			return true;
		}

        // Если пользователь хочет создать в пространстве юзеров чужую страничку - запретить
        if ($title->getNamespace() == NS_USER && $action == 'create') {
            if ($title->getText() !== $user->getname()){
                $result = false;
                return false;
            }
        }

        $pageId = $title->getArticleId();
        $userId = $user->getId();
        $userGroupManager = MediaWikiServices::getInstance()->getUserGroupManager();
        $userGroups = $userGroupManager->getUserGroups($user);
        $isAdmin = in_array("sysop", $userGroups);

        if ($pageId) {
            $isAuthor = OnlyAuthorCanEditPage::getRecordPageAuthor($pageId, $userId);
        } else {
            // если страница еще не сущетсвует, то давать редактировать
            $result = null;
            return true;
        }

        if ($isAuthor || $isAdmin) {
            $result = null;
            return true;
        } else {
            $result = false;
            return false;
        }

    }

    /**
	 * @param WikiPage $wikiPage
	 * @param MediaWiki\User\UserIdentity $user
     * @param string $summary
     * @param int $flags
     * @param MediaWiki\Revision\RevisionRecord $revisionRecord
     * @param MediaWiki\Storage\EditResult $editResult
	 *
	 * @return bool
	 */
    public static function onPageSaveComplete($wikiPage, $user, $summary, $flags, $revisionRecord, $editResult ) {
        $pageId = $wikiPage->getId();
        $authorId = $user->getId();
        $res = OnlyAuthorCanEditPage::getRecordPageAuthor($pageId, $authorId);

        if (!$res) {
            $dbProvider = MediaWikiServices::getInstance()->getConnectionProvider();
            $targetRow = [
                'page_id' => $pageId,
                'author_id' => $authorId
            ];
            $dbw = $dbProvider->getPrimaryDatabase();
            $dbw->newInsertQueryBuilder()
            ->insertInto("page_authors")
            ->row( $targetRow )
            ->caller( __METHOD__ )->execute();
        }
        return true;
    }
}
