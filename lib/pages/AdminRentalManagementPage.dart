import 'package:flutter/material.dart';
import 'package:login_farmer/models/booking_model.dart';
import 'package:login_farmer/service/booking_service.dart';

class AdminRentalManagementPage extends StatefulWidget {
  @override
  _AdminRentalManagementPageState createState() =>
      _AdminRentalManagementPageState();
}

class _AdminRentalManagementPageState extends State<AdminRentalManagementPage> {
  List<BookingModel> _rentals = [];
  bool _isLoading = true;
  final BookingService _bookingService = BookingService();

  @override
  void initState() {
    super.initState();
    _fetchRentals();
  }

  Future<void> _fetchRentals() async {
    setState(() => _isLoading = true);
    try {
      final rentals = await _bookingService.getAllRentals();
      setState(() {
        _rentals = rentals;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to load rentals: $e')),
      );
    }
  }

  void _updateRentalStatus(BookingModel booking, String status) async {
    try {
      await _bookingService.adminUpdateBooking(booking.id, status,
          adminNotes:
              status == 'rejected' ? await _showRejectionReasonDialog() : '');
      _fetchRentals(); // Refresh the list
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to update rental: $e')),
      );
    }
  }

  Future<String> _showRejectionReasonDialog() async {
    return await showDialog(
          context: context,
          builder: (context) {
            final reasonController = TextEditingController();
            return AlertDialog(
              title: Text('Reason for Rejection'),
              content: TextField(
                controller: reasonController,
                decoration: InputDecoration(
                  hintText: 'Enter rejection reason',
                ),
              ),
              actions: [
                TextButton(
                  child: Text('Cancel'),
                  onPressed: () => Navigator.of(context).pop(''),
                ),
                TextButton(
                  child: Text('Submit'),
                  onPressed: () =>
                      Navigator.of(context).pop(reasonController.text),
                ),
              ],
            );
          },
        ) ??
        '';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Rental Management'),
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator())
          : ListView.builder(
              itemCount: _rentals.length,
              itemBuilder: (context, index) {
                final rental = _rentals[index];
                return Card(
                  child: ListTile(
                    title: Text(rental.tractorName),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Farmer: ${rental.customerName}'),
                        Text('Date: ${rental.startDate}'),
                        Text('Status: ${rental.status}'),
                      ],
                    ),
                    trailing: rental.status == 'pending'
                        ? Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              IconButton(
                                icon: Icon(Icons.check, color: Colors.green),
                                onPressed: () =>
                                    _updateRentalStatus(rental, 'approved'),
                              ),
                              IconButton(
                                icon: Icon(Icons.close, color: Colors.red),
                                onPressed: () =>
                                    _updateRentalStatus(rental, 'rejected'),
                              ),
                            ],
                          )
                        : null,
                  ),
                );
              },
            ),
    );
  }
}
