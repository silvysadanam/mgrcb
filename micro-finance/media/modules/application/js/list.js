"use strict";

$(document).ready(function () {
        for (var columns = 2; columns < 12; columns++) {
            var table = "table#list-table-" + columns.toString();
            if ($(table).length > 0) {
                $(table).DataTable( {
                        "aaSorting" : [],
                        "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                        columnDefs: [ {
                                targets: [ columns ],
                                searchable: false
                            }
                        ]
                    }
                );
            }
        }
        /*
        $("table#list-table-2").DataTable( {
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 2 ],
                        searchable: false
                    }
                ]
            }
        );

        $("table#list-table-3").DataTable( {
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 3 ],
                        searchable: false
                    }
                ]
            }
        );

        $("table#list-table-4").DataTable( {
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 4 ],
                        searchable: false
                    }
                ]
            }
        );

        $("table#list-table-5").DataTable( {
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 5 ],
                        searchable: false
                    }
                ]
            }
        );

        $("table#list-table-6").DataTable( {
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 6 ],
                        searchable: false
                    }
                ]
            }
        );

        $("table#list-table-7").DataTable( {
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 7 ],
                        searchable: false
                    }
                ]
            }
        );

        $("table#list-table-8").DataTable( {
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 8 ],
                        searchable: false
                    }
                ]
            }
        );

        $("table#list-table-9").DataTable( {
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 9 ],
                        searchable: false
                    }
                ]
            }
        );

        $("table#list-table-10").DataTable( {
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 10 ],
                        searchable: false
                    }
                ]
            }
        );

        $("table#list-table-11").DataTable( {
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 11 ],
                        searchable: false
                    }
                ]
            }
        );
        */
    }
);
