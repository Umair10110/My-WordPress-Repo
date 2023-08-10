<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WordPress;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\Exceptions\WordPressRepositoryException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use WP_Term;

/**
 * A repository for handling WordPress terms & taxonomies.
 */
class TermsRepository
{
    /**
     * Gets a WordPress term from ID or slug for a given taxonomy.
     *
     * @param int|string $identifier term ID or slug
     * @param string $taxonomyName the taxonomy the term belongs to - required if retrieving by slug
     * @return WP_Term|null
     */
    public static function getTerm($identifier, string $taxonomyName = '') : ?WP_Term
    {
        $term = null;

        if (is_int($identifier)) {
            $term = get_term($identifier, $taxonomyName);
        } elseif (is_string($identifier)) {
            $term = get_term_by('slug', $identifier, $taxonomyName);
        }

        return $term instanceof WP_Term ? $term : null;
    }

    /**
     * Gets WordPress terms based on query arguments.
     *
     * @link https://developer.wordpress.org/reference/classes/wp_term_query/__construct/ for accepted args
     *
     * @param array<string, mixed> $args
     * @return WP_Term[]|int[]|string[]
     * @throws WordPressRepositoryException
     */
    public static function getTerms(array $args) : array
    {
        $terms = get_terms($args);

        if (WordPressRepository::isError($terms)) {
            /* @phpstan-ignore-next-line */
            throw new WordPressRepositoryException($terms->get_error_message());
        }

        /* @phpstan-ignore-next-line */
        return ArrayHelper::wrap($terms);
    }
}
