Feature: CSV Context
  In order to ensure CSV data is encoded properly
  As a developer
  I need to be able to assert whether a given CSV string contains the proper data

  Background:
    Given the following string is stored as "spreadsheet":
          """
          Type,Favorite,Least Favorite
          Grass,Snivy,Gogoat
          Fire,Fennekin,Charizard
          Water,Swampert,Froakie
          Electric,Ampharos,Emolga
          """

    Scenario: Can assert data order-independently for columns
      Then the "spreadsheet" should be CSV data as follows:
         | Least Favorite | Type     | Favorite |
         | Gogoat         | Grass    | Snivy    |
         | Charizard      | Fire     | Fennekin |
         | Froakie        | Water    | Swampert |
         | Emolga         | Electric | Ampharos |

    Scenario: Data assertion does not need to contain all columns in the CSV
      Then the "spreadsheet" should be CSV data as follows:
         | Type     |
         | Grass    |
         | Fire     |
         | Water    |
         | Electric |

    Scenario: Data assertion is order-dependent for rows
      When I assert that the "spreadsheet" should be CSV data as follows:
         | Least Favorite | Type     | Favorite |
         | Froakie        | Water    | Swampert |
         | Charizard      | Fire     | Fennekin |
         | Gogoat         | Grass    | Snivy    |
         | Emolga         | Electric | Ampharos |
      Then the assertion should throw an Exception
       And the assertion should fail with the message "Expected 'Froakie' for 'Least Favorite' in row 1, but found 'Gogoat'"

    Scenario: Data assertion fails properly if an expected column was not found
      When I assert that the "spreadsheet" should be CSV data as follows:
         | Least Favorite | Type     | Favorite | 1st-gen Favorite |
         | Gogoat         | Grass    | Snivy    | Bulbasaur        |
         | Charizard      | Fire     | Fennekin | Ninetales        |
         | Froakie        | Water    | Swampert | Tentacool        |
         | Emolga         | Electric | Ampharos | Raichu           |
      Then the assertion should throw an Exception
       And the assertion should fail with the message "Column 1st-gen Favorite does not exist, but was expected to"

    Scenario: Data assertion fails properly if a value does not match
      When I assert that the "spreadsheet" should be CSV data as follows:
         | Type     |
         | Poison   |
         | Fire     |
         | Water    |
         | Electric |
      Then the assertion should throw an Exception
       And the assertion should fail with the message "Expected 'Poison' for 'Type' in row 1, but found 'Grass'"

    Scenario: Data assertion fails properly if there are fewer rows than expected
      When I assert that the "spreadsheet" should be CSV data as follows:
         | Type     |
         | Grass    |
         | Fire     |
         | Water    |
         | Electric |
         | Poison   |
      Then the assertion should throw an Exception
       And the assertion should fail with the message "Expected 6 rows, but found 5"

    Scenario: Data assertion fails properly if there are fewer rows than expected
      When I assert that the "spreadsheet" should be CSV data as follows:
         | Type     |
         | Grass    |
         | Fire     |
         | Water    |
      Then the assertion should throw an Exception
       And the assertion should fail with the message "Expected 4 rows, but found 5"

    Scenario: Can assert the proper headers order-independently
      Then the "spreadsheet" should be CSV data with the following headers:
         | Favorite       |
         | Type           |
         | Least Favorite |

    Scenario: Header assertion fails properly if headers are not present
      When I assert that the "spreadsheet" should be CSV data with the following headers:
         | Favorite         |
         | Type             |
         | Least Favorite   |
         | 1st-gen Favorite |
         | 2nd-gen Favorite |
      Then the assertion should throw an Exception
       And the assertion should fail with the message "CSV 'spreadsheet' is missing headers '1st-gen Favorite', '2nd-gen Favorite'"

    Scenario: Header assertion fails properly if extra headers are present
      When I assert that the "spreadsheet" should be CSV data with the following headers:
         | Favorite |
      Then the assertion should throw an Exception
       And the assertion should fail with the message "CSV 'spreadsheet' contains extra headers 'Type', 'Least Favorite'"

    Scenario: Step injects values properly into CSV
      Given the following is stored as "list":
        | first_entry  | Line one   |
        | second_entry | Line two   |
        | third_entry  | Line three |
      Given the following string is stored as "spreadsheet":
          """
          (the first entry of the list),(the second entry of the list),(the third entry of the list)
          """
      Then the "spreadsheet" should be CSV data as follows:
        | Line one         | Line two    | Line three    |
