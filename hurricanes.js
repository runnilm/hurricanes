$(document).ready(function () {
    $.ajax({
        url: "hurricanes.ajax.php",
        type: "POST",
        data: { action: "getColumns" },
        dataType: "json",
    }).done(function (columnsData) {
        var columns = columnsData.map(function (colData) {
            return { data: colData.COLUMN_NAME };
        });

        var thead = $("<thead><tr></tr></thead>");
        var tr = thead.find("tr");
        tr.append("<th></th>");
        columnsData.forEach(function (colData) {
            var th = $("<th>")
                .attr("data-bs-toggle", "tooltip")
                .attr("data-bs-placement", "top")
                .attr("title", colData.COLUMN_COMMENT || "")
                .attr("tabindex", "-1")
                .text(colData.COLUMN_NAME);
            tr.append(th);
        });

        $("#hurricanes").append(thead);

        var table = $("#hurricanes").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "hurricanes.ajax.php",
                type: "POST",
            },
            columns: [{ data: null, orderable: false, className: 'dt-control' }].concat(columns),
            scrollX: true,
            fixedHeader: true,
        });

        table.on('draw', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl, {
                    trigger: 'hover'
                });
            });
        });

        // A global cache object to store detailRecords by SID
        var childDataCache = {};

        $('#hurricanes tbody').on('click', 'td.dt-control', function () {
            var row = table.row($(this).closest('tr'));
            var rowData = row.data();
            var sid = rowData.SID;

            if (row.child.isShown()) {
                // Close child row
                row.child.hide();
                $(this).closest('tr').removeClass('shown');
            } else {
                // Check if we have cached data
                if (childDataCache[sid]) {
                    // Use cached data
                    showChildTable(row, sid, childDataCache[sid]);
                    $(this).closest('tr').addClass('shown');
                } else {
                    // Fetch data from server
                    $.ajax({
                        url: 'hurricanes.ajax.php',
                        type: 'POST',
                        data: { action: 'getDetails', sid: sid },
                        dataType: 'json',
                        success: function (detailRecords) {
                            // Cache the data
                            childDataCache[sid] = detailRecords;
                            showChildTable(row, sid, detailRecords);
                            $(this).closest('tr').addClass('shown');
                        }.bind(this)
                    });
                }
            }
        });

        // A helper function to create and show the child DataTable
        function showChildTable(row, sid, detailRecords) {
            var childTableId = 'childTable_' + sid;

            // Build child table HTML
            var childHtml = '<table id="' + childTableId + '" class="table table-sm" style="width:100%"><thead><tr>';
            var detailColumns = [];
            columnsData.forEach(function (col) {
                childHtml += '<th>' + col.COLUMN_NAME + '</th>';
                detailColumns.push({ data: col.COLUMN_NAME });
            });
            childHtml += '</tr></thead></table>';

            row.child(childHtml).show();

            // Initialize DataTable on the child table if not already initialized
            // To avoid re-initializing if row closed and opened again, check if DataTable exists
            if (!$.fn.DataTable.isDataTable('#' + childTableId)) {
                $('#' + childTableId).DataTable({
                    data: detailRecords,
                    columns: detailColumns,
                    scrollX: true,
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                });
            }
        }
    });
});
