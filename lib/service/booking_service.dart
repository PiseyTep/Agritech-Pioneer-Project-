import 'dart:convert';
import 'package:login_farmer/main.dart';
import 'package:login_farmer/models/booking_model.dart';
import 'package:login_farmer/service/api_service.dart';
import 'package:shared_preferences/shared_preferences.dart';

class BookingService {
  final ApiService _apiService = getIt<ApiService>();

  // Get all bookings for the current user
  Future<List<BookingModel>> getBookings() async {
    try {
      final result = await _apiService.getData('user/rentals');

      if (result['success'] == true && result['data'] != null) {
        final List bookingsJson = result['data'];
        return bookingsJson.map((json) => BookingModel.fromJson(json)).toList();
      } else {
        throw Exception(result['message'] ?? 'Failed to load bookings');
      }
    } catch (e) {
      // Fall back to local storage if API fails
      return _getLocalBookings();
    }
  }

  // Save a booking through the API
  Future<Map<String, dynamic>> saveBooking(BookingModel booking) async {
    try {
      // Map BookingModel to the API's expected format
      final Map<String, dynamic> apiData = {
        'tractor_id': booking.tractorId,
        'rental_date': booking.startDate,
        'return_date': booking.endDate,
        'total_price': booking.totalPrice,
        'customer_name': booking.customerName,
        'customer_phone': booking.customerPhone,
        'customer_address': booking.customerAddress,
        'land_size': booking.landSize,
        'land_size_unit': booking.landSizeUnit,
        'notes': booking.adminNotes ?? '',
      };

      // Send to API
      final result = await _apiService.postData('user/rentals', apiData);

      // If successful, also save locally as backup
      if (result['success'] == true) {
        await _saveLocalBooking(booking);
      }

      return result;
    } catch (e) {
      // If API fails, save locally and return error
      await saveOfflineBooking(booking);
      throw Exception('Failed to save booking to API: $e');
    }
  }

// Update booking status
  Future<Map<String, dynamic>> updateBookingStatus(
      String id, String status) async {
    try {
      final result =
          await _apiService.putData('user/rentals/$id', {'status': status});

      // Update local copy too
      if (result['success'] == true) {
        await _updateLocalBookingStatus(id, status);
      }

      return result;
    } catch (e) {
      // Update locally if API fails
      await _updateLocalBookingStatus(id, status);
      throw Exception('Failed to update booking status: $e');
    }
  }

// Cancel a booking
  Future<Map<String, dynamic>> cancelBooking(String id) async {
    try {
      final result = await _apiService.deleteData('user/rentals/$id/cancel');

      // Update local copy too
      if (result['success'] == true) {
        await _updateLocalBookingStatus(id, 'Cancelled');
      }

      return result;
    } catch (e) {
      // Update locally if API fails
      await _updateLocalBookingStatus(id, 'Cancelled');
      throw Exception('Failed to cancel booking: $e');
    }
  }

// Update booking status in local storage
  Future<void> _updateLocalBookingStatus(String id, String status) async {
    try {
      final prefs = await SharedPreferences.getInstance();

      // Get existing bookings
      List<String> bookingsJson = prefs.getStringList('bookings') ?? [];

      // Create a new list to store updated bookings
      List<String> updatedBookings = [];

      for (String bookingJson in bookingsJson) {
        try {
          // Parse the booking
          BookingModel booking = BookingModel.fromJsonString(bookingJson);

          // If this is the booking we want to update
          if (booking.id == id) {
            // Create an updated booking with new status
            BookingModel updatedBooking = booking.copyWith(status: status);
            updatedBookings.add(updatedBooking.toJsonString());
          } else {
            // Keep the original booking
            updatedBookings.add(bookingJson);
          }
        } catch (e) {
          // If there's an error parsing a specific booking, skip it
          print('Error processing booking: $e');
          updatedBookings.add(bookingJson);
        }
      }

      // Save back to preferences
      await prefs.setStringList('bookings', updatedBookings);
    } catch (e) {
      print('Failed to update local booking status: $e');
      throw Exception('Failed to update local booking status: $e');
    }
  }

  // Admin approve/reject booking
  Future<Map<String, dynamic>> adminUpdateBooking(String id, String status,
      {String adminNotes = ''}) async {
    try {
      final result = await _apiService.putData(
          'admin/rentals/$id', {'status': status, 'admin_notes': adminNotes});

      if (result['success'] == true) {
        // Update local booking
        await _updateLocalBookingAdminStatus(id, status, adminNotes);
      }

      return result;
    } catch (e) {
      throw Exception('Failed to update booking: $e');
    }
  }

  // Get all rentals for admin dashboard
  Future<List<BookingModel>> getAllRentals({String? status}) async {
    try {
      final queryParams = status != null ? '?status=$status' : '';
      final result = await _apiService.getData('admin/rentals$queryParams');

      if (result['success'] == true && result['data'] != null) {
        final List bookingsJson = result['data'];
        return bookingsJson.map((json) => BookingModel.fromJson(json)).toList();
      } else {
        throw Exception(result['message'] ?? 'Failed to load rentals');
      }
    } catch (e) {
      // Fallback to local storage if API fails
      return _getLocalBookings();
    }
  }

  // Update local booking with admin status
  Future<void> _updateLocalBookingAdminStatus(
      String id, String status, String adminNotes) async {
    try {
      final prefs = await SharedPreferences.getInstance();

      // Get existing bookings
      List<String> bookingsJson = prefs.getStringList('bookings') ?? [];

      // Find and update the booking
      for (int i = 0; i < bookingsJson.length; i++) {
        try {
          BookingModel booking = BookingModel.fromJsonString(bookingsJson[i]);

          if (booking.id == id) {
            // Update status and admin notes
            BookingModel updatedBooking = booking.copyWith(
              status: status,
              isAcceptedByAdmin: status == 'approved',
              adminNotes: adminNotes,
            );
            bookingsJson[i] = updatedBooking.toJsonString();
            break;
          }
        } catch (e) {
          print('Error processing booking: $e');
          continue;
        }
      }

      // Save back to preferences
      await prefs.setStringList('bookings', bookingsJson);
    } catch (e) {
      print('Failed to update local booking admin status: $e');
      throw Exception('Failed to update local booking admin status: $e');
    }
  }

  // Sync offline admin bookings
  Future<Map<String, dynamic>> syncOfflineAdminActions() async {
    try {
      final prefs = await SharedPreferences.getInstance();

      // Get offline admin actions
      List<String> offlineAdminActions =
          prefs.getStringList('offline_admin_actions') ?? [];

      if (offlineAdminActions.isEmpty) {
        return {'success': true, 'message': 'No offline admin actions to sync'};
      }

      int successCount = 0;
      int failCount = 0;
      List<String> remainingActions = [];

      // Try to sync each admin action
      for (String actionJson in offlineAdminActions) {
        try {
          // Parse the offline action
          Map<String, dynamic> action = jsonDecode(actionJson);

          // Attempt to apply the admin action
          final result = await adminUpdateBooking(
            action['id'],
            action['status'],
            adminNotes: action['adminNotes'] ?? '',
          );

          if (result['success'] == true) {
            successCount++;
          } else {
            remainingActions.add(actionJson);
            failCount++;
          }
        } catch (e) {
          remainingActions.add(actionJson);
          failCount++;
        }
      }

      // Update offline actions list
      await prefs.setStringList('offline_admin_actions', remainingActions);

      return {
        'success': true,
        'message': 'Synced $successCount admin actions. Failed: $failCount'
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Failed to sync offline admin actions: $e'
      };
    }
  }

  // Save offline admin action for later sync
  Future<void> saveOfflineAdminAction(
      String bookingId, String status, String adminNotes) async {
    try {
      final prefs = await SharedPreferences.getInstance();

      // Get existing offline admin actions
      List<String> offlineActions =
          prefs.getStringList('offline_admin_actions') ?? [];

      // Prepare the action data
      final actionData = jsonEncode({
        'id': bookingId,
        'status': status,
        'adminNotes': adminNotes,
      });

      // Add new action
      offlineActions.add(actionData);

      // Save back to preferences
      await prefs.setStringList('offline_admin_actions', offlineActions);
    } catch (e) {
      throw Exception('Failed to save offline admin action: $e');
    }
  }

  // Save booking to local storage for offline mode
  Future<void> saveOfflineBooking(BookingModel booking) async {
    try {
      final prefs = await SharedPreferences.getInstance();

      // Get existing offline bookings
      List<String> offlineBookings =
          prefs.getStringList('offline_bookings') ?? [];

      // Add new booking
      offlineBookings.add(booking.toJsonString());

      // Save back to preferences
      await prefs.setStringList('offline_bookings', offlineBookings);

      // Also save to regular bookings list
      await _saveLocalBooking(booking);
    } catch (e) {
      throw Exception('Failed to save offline booking: $e');
    }
  }

  // Sync offline bookings
  Future<Map<String, dynamic>> syncOfflineBookings() async {
    try {
      final prefs = await SharedPreferences.getInstance();

      // Get offline bookings
      List<String> offlineBookings =
          prefs.getStringList('offline_bookings') ?? [];

      if (offlineBookings.isEmpty) {
        return {'success': true, 'message': 'No offline bookings to sync'};
      }

      int successCount = 0;
      int failCount = 0;
      List<String> remainingBookings = [];

      // Try to sync each booking
      for (String bookingJson in offlineBookings) {
        try {
          BookingModel booking = BookingModel.fromJsonString(bookingJson);

          // Skip if already synced (has a non-offline ID)
          if (!booking.id.startsWith('offline_')) {
            successCount++;
            continue;
          }

          // Prepare API data
          final Map<String, dynamic> apiData = {
            'tractor_id': booking.tractorId,
            'rental_date': booking.startDate,
            'return_date': booking.endDate,
            'total_price': booking.totalPrice,
            'customer_name': booking.customerName,
            'customer_phone': booking.customerPhone,
            'customer_address': booking.customerAddress,
            'land_size': booking.landSize,
            'land_size_unit': booking.landSizeUnit,
            'notes': booking.adminNotes ?? '',
            'status': booking.status ?? 'pending',
          };

          // Send to API
          final result = await _apiService.postData('user/rentals', apiData);

          if (result['success'] == true) {
            successCount++;
          } else {
            // Keep the booking for future sync attempts
            remainingBookings.add(bookingJson);
            failCount++;
          }
        } catch (e) {
          print('Error syncing offline booking: $e');
          // Keep the booking for future sync attempts
          remainingBookings.add(bookingJson);
          failCount++;
        }
      }

      // Update offline bookings list
      await prefs.setStringList('offline_bookings', remainingBookings);

      return {
        'success': true,
        'message': 'Synced $successCount bookings. Failed: $failCount'
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Failed to sync offline bookings: $e'
      };
    }
  }

  // Get bookings from local storage
  Future<List<BookingModel>> _getLocalBookings() async {
    try {
      final prefs = await SharedPreferences.getInstance();

      // Get all bookings
      List<String> bookingsJson = prefs.getStringList('bookings') ?? [];

      return bookingsJson
          .map((json) => BookingModel.fromJsonString(json))
          .toList();
    } catch (e) {
      return [];
    }
  }

  // Save a booking to local storage
  Future<void> _saveLocalBooking(BookingModel booking) async {
    try {
      final prefs = await SharedPreferences.getInstance();

      // Get existing bookings
      List<String> bookings = prefs.getStringList('bookings') ?? [];

      // Check if booking already exists
      int existingIndex = bookings.indexWhere((item) {
        try {
          BookingModel existing = BookingModel.fromJsonString(item);
          return existing.id == booking.id;
        } catch (e) {
          return false;
        }
      });

      // Update or add
      if (existingIndex >= 0) {
        bookings[existingIndex] = booking.toJsonString();
      } else {
        bookings.add(booking.toJsonString());
      }

      // Save back to preferences
      await prefs.setStringList('bookings', bookings);
    } catch (e) {
      throw Exception('Failed to save local booking: $e');
    }
  }
}
