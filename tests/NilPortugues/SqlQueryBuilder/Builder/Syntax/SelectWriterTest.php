<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 9/12/14
 * Time: 10:46 PM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\NilPortugues\SqlQueryBuilder\Builder\Syntax;

use NilPortugues\SqlQueryBuilder\Builder\GenericBuilder;
use NilPortugues\SqlQueryBuilder\Manipulation\Select;
use NilPortugues\SqlQueryBuilder\Syntax\OrderBy;

/**
 * Class SelectWriterTest
 * @package Tests\NilPortugues\SqlQueryBuilder\Builder\Syntax
 */
class SelectWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GenericBuilder
     */
    private $writer;

    /**
     * @var Select
     */
    private $query;

    /**
     * @var string
     */
    private $exceptionClass = '\NilPortugues\SqlQueryBuilder\Manipulation\QueryException';

    /**
     *
     */
    protected function setUp()
    {
        $this->writer = new GenericBuilder();
        $this->query  = new Select();
    }

    /**
     * @test
     */
    public function it_should_be_cloneable_without_keeping_references()
    {
        $query1 = new Select('user');
        $query2 = clone $query1;
        $query2->setTable('users');

        $this->assertFalse($query1->getTable() == $query2->getTable());
    }

    /**
     * @test
     */
    public function it_should_be_constructed_with_constructor()
    {
        $this->query = new Select('user');

        $expected = 'SELECT user.* FROM user';

        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_throw_exception_when_getting_columns_but_no_table_is_set()
    {
        $this->setExpectedException($this->exceptionClass);

        $this->query = new Select();
        $this->query->getColumns();
    }

    /**
     * @test
     */
    public function it_should_be_constructed_with_constructor_with_columns()
    {
        $this->query = new Select('user', array('user_id', 'name'));

        $expected = 'SELECT user.user_id, user.name FROM user';

        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_select_all()
    {
        $this->query->setTable('user');

        $expected = 'SELECT user.* FROM user';

        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_select_all_distinct()
    {
        $this->query->setTable('user')->distinct();

        $expected = 'SELECT DISTINCT user.* FROM user';

        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_select_all_with_limit_1()
    {
        $this->query->setTable('user')->limit(1);

        $expected = 'SELECT user.* FROM user LIMIT :v1, :v2';

        $this->assertSame($expected, $this->writer->write($this->query));

        $expected = array(':v1' => 1, ':v2' => 0);
        $this->assertEquals($expected, $this->writer->getValues());
    }

    /**
     * @test
     */
    public function it_should_select_all_with_limit_1_offset_2()
    {
        $this->query->setTable('user')->limit(1, 2);

        $expected = 'SELECT user.* FROM user LIMIT :v1, :v2';

        $this->assertSame($expected, $this->writer->write($this->query));

        $expected = array(':v1' => 1, ':v2' => 2);
        $this->assertEquals($expected, $this->writer->getValues());
    }

    /**
     * @test
     */
    public function it_should_select_all_get_first_20()
    {
        $this->query->setTable('user')->limit(0, 20);

        $expected = 'SELECT user.* FROM user LIMIT :v1, :v2';

        $this->assertSame($expected, $this->writer->write($this->query));

        $expected = array(':v1' => 0, ':v2' => 20);
        $this->assertEquals($expected, $this->writer->getValues());
    }

    /**
     * @test
     */
    public function it_should_allow_column_alias()
    {
        $this->query
            ->setTable('user')
            ->setColumns(
                array(
                    'userId'   => 'user_id', // Alias -> column name
                    'username' => 'name',
                    'email'    => 'email',
                )
            );

        $expected = "SELECT user.user_id AS 'userId', user.name AS 'username', user.email AS 'email' FROM user";

        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_allow_column_order()
    {
        $this->query
            ->setTable('user')
            ->orderBy('user_id', OrderBy::ASC);

        $expected = 'SELECT user.* FROM user ORDER BY user.user_id ASC';

        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_allow_column_order_using_column_alias()
    {
        $tableName = 'user';
        $this->query
            ->setTable($tableName)
            ->setColumns(
                array(
                    'userId'   => 'user_id', // Alias -> column name
                    'username' => 'name',
                    'email'    => 'email',
                )
            )
            ->orderBy('user_id', OrderBy::ASC)
            ->orderBy('email', OrderBy::DESC);

        $expected =
            'SELECT user.user_id AS \'userId\', user.name AS \'username\', user.email AS \'email\' FROM '.
            'user ORDER BY user.user_id ASC, user.email DESC';

        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_be_able_to_do_a_left_join()
    {
        $this->query
            ->setTable('user')
            ->leftJoin('news', 'user_id', 'author_id', array('title', 'body', 'created_at', 'updated_at'));

        $expected = 'SELECT user.*, news.title, news.body, news.created_at, news.updated_at FROM user LEFT JOIN '.
            'news ON (news.author_id = user.user_id)';
        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_be_able_to_do_a_right_join()
    {
        $this->query
            ->setTable('user')
            ->rightJoin('news', 'user_id', 'author_id', array('title', 'body', 'created_at', 'updated_at'));

        $expected = 'SELECT user.*, news.title, news.body, news.created_at, news.updated_at FROM user RIGHT JOIN '.
            'news ON (news.author_id = user.user_id)';
        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_be_able_to_do_a_inner_join()
    {
        $this->query
            ->setTable('user')
            ->innerJoin('news', 'user_id', 'author_id', array('title', 'body', 'created_at', 'updated_at'));

        $expected = 'SELECT user.*, news.title, news.body, news.created_at, news.updated_at FROM user INNER JOIN '.
            'news ON (news.author_id = user.user_id)';
        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_be_able_to_do_a_cross_join()
    {
        $this->query
            ->setTable('user')
            ->crossJoin('news', 'user_id', 'author_id', array('title', 'body', 'created_at', 'updated_at'));

        $expected = 'SELECT user.*, news.title, news.body, news.created_at, news.updated_at FROM user CROSS JOIN '.
            'news ON (news.author_id = user.user_id)';
        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_be_able_to_do_a_left_join_with_order_by_on_joined_table()
    {
        $this->query
            ->setTable('user')
            ->setColumns(
                array(
                    'userId'   => 'user_id',
                    'username' => 'name',
                    'email'    => 'email',
                    'created_at',
                )
            )
            ->orderBy('user_id', OrderBy::DESC)
            ->leftJoin('news', 'user_id', 'author_id', array('title', 'body', 'created_at', 'updated_at'))
            ->orderBy('created_at', OrderBy::DESC);

        $expected = 'SELECT user.user_id AS \'userId\', user.name AS \'username\', user.email AS \'email\', user.created_at,'.
            ' news.title, news.body, news.created_at, news.updated_at FROM user LEFT JOIN news ON (news.author_id '.
            '= user.user_id) ORDER BY user.user_id DESC, news.created_at DESC';

        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_be_able_to_do_a_join()
    {
        $this->query
            ->setTable('user')
            ->join('news', 'user_id', 'author_id', array('title', 'body', 'created_at', 'updated_at'));

        $expected = 'SELECT user.*, news.title, news.body, news.created_at, news.updated_at FROM user JOIN '.
            'news ON (news.author_id = user.user_id)';
        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_be_able_to_do_a_join_with_order_by_on_joined_table()
    {
        $this->query
            ->setTable('user')
            ->setColumns(
                array(
                    'userId'   => 'user_id',
                    'username' => 'name',
                    'email'    => 'email',
                    'created_at',
                )
            )
            ->orderBy('user_id', OrderBy::DESC)
            ->join('news', 'user_id', 'author_id', array('title', 'body', 'created_at', 'updated_at'))
            ->orderBy('created_at', OrderBy::DESC);

        $expected = 'SELECT user.user_id AS \'userId\', user.name AS \'username\', user.email AS \'email\', user.created_at,'.
            ' news.title, news.body, news.created_at, news.updated_at FROM user JOIN news ON (news.author_id ='.
            ' user.user_id) ORDER BY user.user_id DESC, news.created_at DESC';

        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_be_able_to_do_an_add_with_multiple_joins()
    {
        $this->query->setTable('user');

        for ($i = 1; $i <= 5; $i++) {
            //Select QueryInterface for "news" table
            $select = new Select();
            $select
                ->setTable('news'.$i)
                ->setColumns(array('title'.$i));

            //Select query for user table, being joined with "newsX" select.
            $this->query->addJoin($select, 'user_id', 'author_id'.$i);
        }

        $expected = 'SELECT user.*, news1.title1, news2.title2, news3.title3, news4.title4, news5.title5 '.
            'FROM user JOIN news1 ON (news1.author_id1 = user.user_id) JOIN news2 ON (news2.author_id2 = user.user_id)'.
            ' JOIN news3 ON (news3.author_id3 = user.user_id) JOIN news4 ON (news4.author_id4 = user.user_id) '.
            'JOIN news5 ON (news5.author_id5 = user.user_id)';

        $this->assertSame($expected, $this->writer->write($this->query));
    }

    /**
     * @test
     */
    public function it_should_be_able_to_on()
    {
        $this->query
            ->setTable('user')
            ->setColumns(
                array(
                    'userId'   => 'user_id',
                    'username' => 'name',
                    'email'    => 'email',
                    'created_at',
                )
            )
            ->orderBy('user_id', OrderBy::DESC)
            ->join('news', 'user_id', 'author_id', array('title', 'body', 'created_at', 'updated_at'))
            ->orderBy('created_at', OrderBy::DESC)
            ->on()
            ->eq('author_id', 1);

        $this->query->limit(1, 10);

        $expected = 'SELECT user.user_id AS \'userId\', user.name AS \'username\', user.email AS \'email\', user.created_at,'.
            ' news.title, news.body, news.created_at, news.updated_at FROM user JOIN news ON '.
            '(news.author_id = user.user_id) AND (news.author_id = :v1) ORDER BY '.
            'user.user_id DESC, news.created_at DESC LIMIT :v2, :v3';

        $this->assertSame($expected, $this->writer->write($this->query));

        $expected = array(':v1' => 1, ':v2' => 1, ':v3' => 10);
        $this->assertEquals($expected, $this->writer->getValues());
    }

    /**
     * @test
     */
    public function it_should_be_able_to_count_total_rows()
    {
        $this->query
            ->setTable('user')
            ->count()
            ->groupBy(array('user_id', 'name'))
            ->having()
            ->equals('user_id', 1)
            ->equals('user_id', 2);

        $expected = 'SELECT COUNT(*) FROM user GROUP BY user.user_id, user.name HAVING (user.user_id = :v1) AND (user.user_id = :v2)';

        $this->assertSame($expected, $this->writer->write($this->query));
        $expected = array(':v1' => 1, ':v2' => 2);
        $this->assertEquals($expected, $this->writer->getValues());
    }

    /**
     * @test
     */
    public function it_should_be_able_to_count_total_rows_setting_default_column()
    {
        $this->query
            ->setTable('user')
            ->count('user_id')
            ->groupBy(array('user_id', 'name'))
            ->having()
            ->equals('user_id', 1)
            ->equals('user_id', 2);

        $expected = 'SELECT COUNT(user.user_id) FROM user GROUP BY user.user_id, user.name HAVING (user.user_id = :v1) AND (user.user_id = :v2)';

        $this->assertSame($expected, $this->writer->write($this->query));
        $expected = array(':v1' => 1, ':v2' => 2);
        $this->assertEquals($expected, $this->writer->getValues());
    }

    /**
     * @test
     */
    public function it_should_be_able_to_count_total_rows_setting_default_column_with_alias()
    {
        $this->query
            ->setTable('user')
            ->count('user_id', 'total_users')
            ->groupBy(array('user_id', 'name'))
            ->having()
            ->equals('user_id', 1)
            ->equals('user_id', 2);

        $expected = 'SELECT COUNT(user.user_id) AS \'total_users\' FROM user GROUP BY user.user_id, user.name HAVING (user.user_id = :v1) AND (user.user_id = :v2)';

        $this->assertSame($expected, $this->writer->write($this->query));
        $expected = array(':v1' => 1, ':v2' => 2);
        $this->assertEquals($expected, $this->writer->getValues());
    }

    /**
     * @test
     */
    public function it_should_be_able_to_group_by_operator()
    {
        $this->query
            ->setTable('user')
            ->setColumns(
                array(
                    'userId'   => 'user_id',
                    'username' => 'name',
                    'email'    => 'email',
                    'created_at',
                )
            )
            ->groupBy(array('user_id', 'name'))
            ->having()
            ->equals('user_id', 1)
            ->equals('user_id', 2);

        $expected = 'SELECT user.user_id AS \'userId\', user.name AS \'username\', user.email AS \'email\', user.created_at'.
            ' FROM user GROUP BY user.user_id, user.name HAVING (user.user_id = :v1) AND (user.user_id = :v2)';

        $this->assertSame($expected, $this->writer->write($this->query));
        $expected = array(':v1' => 1, ':v2' => 2);
        $this->assertEquals($expected, $this->writer->getValues());
    }

    /**
     * @test
     */
    public function it_should_throw_exception_invalid_having_conjunction()
    {
        $this->setExpectedException($this->exceptionClass);

        $this->query
            ->setTable('user')
            ->setColumns(
                array(
                    'userId'   => 'user_id',
                    'username' => 'name',
                    'email'    => 'email',
                    'created_at',
                )
            )
            ->groupBy(array('user_id', 'name'))
            ->having('AAAAAAAAAAAAAAAA');
    }

    /**
     * @test
     */
    public function it_should_be_able_to_set_having_operator_to_OR()
    {
        $this->query
            ->setTable('user')
            ->setColumns(
                array(
                    'userId'   => 'user_id',
                    'username' => 'name',
                    'email'    => 'email',
                    'created_at',
                )
            )
            ->groupBy(array('user_id', 'name'))
            ->having('OR')
            ->equals('user_id', 1)
            ->equals('user_id', 2);

        $expected = 'SELECT user.user_id AS \'userId\', user.name AS \'username\', user.email AS \'email\', user.created_at'.
            ' FROM user GROUP BY user.user_id, user.name HAVING (user.user_id = :v1) OR (user.user_id = :v2)';

        $this->assertSame($expected, $this->writer->write($this->query));
        $expected = array(':v1' => 1, ':v2' => 2);
        $this->assertEquals($expected, $this->writer->getValues());
    }

    /**
     * @test
     */
    public function it_should_allow_select_query_to_act_as_a_column()
    {
        $table1 = new Select('Table1');
        $table1
            ->where()
            ->equals('table1_id', 1);

        $table2 = new Select('Table2');
        $table2
            ->where()
            ->eq($table1, 2);

        $expected = 'SELECT Table2.* FROM Table2 WHERE ((SELECT Table1.* FROM Table1 '.
            'WHERE (Table1.table1_id = :v1)) = :v2)';

        $this->assertSame($expected, $this->writer->write($table2));

        $expected = array(':v1' => 1, ':v2' => 2);
        $this->assertEquals($expected, $this->writer->getValues());
    }

    /**
     * @test
     */
    public function it_should_write_join()
    {
        $this->query
            ->isJoin(true)
            ->setTable('user')
            ->on()
            ->equals('user_id', 1);

        $expected = 'JOIN user ON (user.user_id = :v1)';
        $this->assertSame($expected, $this->writer->write($this->query));

        $expected = array(':v1' => 1);
        $this->assertEquals($expected, $this->writer->getValues());
    }
}
