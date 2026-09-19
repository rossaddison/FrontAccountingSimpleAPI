<?php
namespace FAAPI;

/**
 * @SWG\Definition(
 *   definition="Sale",
 *   type="object",
 *   format="",
 *   description="A Sale",
 *   @SWG\Property(
 *     property="id",
 *     type="integer",
 *     description="Unique id used to reference a Sale",
 *     example="1"
 *   )
 * )
 */
class Sales
{
    /**
     * Finds a non-voided document of one type by its memo (the `comments`
     * value given at creation), so a caller can check "did I already post
     * this" without knowing the FrontAccounting transaction number.
     *
     * @SWG\Get(
     *     path="/sales/lookup",
     *     summary="Find a sales document by trans_type and comments",
     *     tags={"sales"},
     *     operationId="lookupSale",
     *     produces={"application/json"},
     *     @SWG\Response(response=200, description="found: trans_no and reference"),
     *     @SWG\Response(response=404, description="no such document"),
     *     deprecated=false
     * )
     */
    public function lookup($rest)
    {
        // $_GET, not $rest->request()->get(): FrontAccounting's
        // html_cleanup($_SERVER) turns "&" in QUERY_STRING into "&amp;",
        // which Slim then parses as a parameter called "amp;comments".
        $type = (int) ($_GET['trans_type'] ?? 0);
        $memo = (string) ($_GET['comments'] ?? '');
        if ($type === 0 || $memo === '') {
            \api_error(412, 'trans_type and comments are required');
            return;
        }

        $sql = "SELECT dt.trans_no, dt.reference FROM " . TB_PREF . "debtor_trans dt"
            . " JOIN " . TB_PREF . "comments c ON c.type = dt.type AND c.id = dt.trans_no"
            . " LEFT JOIN " . TB_PREF . "voided v ON v.type = dt.type AND v.id = dt.trans_no"
            . " WHERE dt.type = " . db_escape($type)
            . " AND c.memo_ = " . db_escape($memo)
            . " AND v.id IS NULL ORDER BY dt.trans_no DESC LIMIT 1";
        $row = db_fetch(db_query($sql, 'could not look up the sales document'));

        if (!$row) {
            \api_error(404, 'Not found');
            return;
        }
        \api_response(200, array('trans_no' => (int) $row['trans_no'], 'reference' => $row['reference']));
    }

    // Get Items
    /**
     * @SWG\Get(
     *     path="/sales",
     *     summary="List Sales",
     *     tags={"sales"},
     *     operationId="getSales",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response=200,
     *         description="successful operation",
     *         @SWG\Schema(
     *             type="object",
     *             ref="#/definitions/Sale"
     *         )
     *     ),
     *     deprecated=false
     * )
     */
    public function get($rest, $trans_type)
    {
        $req = $rest->request();
        include_once(API_ROOT . "/sales.inc");

        $page = $req->get("page");

        if ($page == null) {
            sales_all($trans_type);
        } else {
            // If page = 1 the value will be 0, if page = 2 the value will be 1, ...
            $from = -- $page * RESULTS_PER_PAGE;
            sales_all($trans_type, $from);
        }
    }

    // Get Specific Item by Sale Id
    /**
     * @SWG\Get(
     *     path="/sales/id",
     *     summary="Fetch Sale by id",
     *     tags={"sales"},
     *     operationId="getSale",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response=200,
     *         description="successful operation",
     *         @SWG\Schema(
     *             type="object",
     *             ref="#/definitions/Sale"
     *         )
     *     ),
     *     deprecated=false
     * )
     */
    public function getById($rest, $trans_no, $trans_type)
    {
        include_once(API_ROOT . "/sales.inc");
        sales_get($trans_no, $trans_type);
    }
    // Add Item
    /**
     * @SWG\Post(
     *     path="/sales",
     *     summary="Add Sale",
     *     tags={"sales"},
     *     operationId="addSale",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response=200,
     *         description="successful operation",
     *         @SWG\Schema(
     *             type="object",
     *             ref="#/definitions/Sale"
     *         )
     *     ),
     *     deprecated=false
     * )
     */
    public function post($rest)
    {
        include_once(API_ROOT . "/sales.inc");
        sales_add();
    }
    // Edit Specific Item
    /**
     * @SWG\Put(
     *     path="/sales",
     *     summary="Update Sale",
     *     tags={"sales"},
     *     operationId="addSale",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response=200,
     *         description="successful operation",
     *         @SWG\Schema(
     *             type="object",
     *             ref="#/definitions/Sale"
     *         )
     *     ),
     *     deprecated=false
     * )
     */
    public function put($rest, $trans_no, $trans_type)
    {
        include_once(API_ROOT . "/sales.inc");
        sales_edit($trans_no, $trans_type);
    }
    // Delete Specific Item
    public function delete($rest, $branch_id, $uuid)
    {
        include_once(API_ROOT . "/sales.inc");
        sales_cancel($branch_id, $uuid);
    }
}
